import axios, { type InternalAxiosRequestConfig } from 'axios'

const baseURL = import.meta.env.VITE_URL_API ?? '/api/v1'
export const httpClient = axios.create({
  baseURL,
  timeout: 10_000,
  headers: {
    Accept: 'application/json',
  },
})

interface ConfiguracaoCsrf extends InternalAxiosRequestConfig { _csrfRepetido?: boolean }
let tokenCsrf: string | null = null
let carregamentoToken: Promise<string> | null = null
const metodosSeguros = new Set(['get', 'head', 'options'])

async function obterTokenCsrf(): Promise<string> {
  if (tokenCsrf) return tokenCsrf
  if (!carregamentoToken) carregamentoToken = axios.get<{ token: string }>(`${baseURL}/auth/csrf`, { timeout: 10_000 }).then(resposta => { tokenCsrf = resposta.data.token; return tokenCsrf }).finally(() => { carregamentoToken = null })
  return carregamentoToken
}

export function limparTokenCsrf(): void { tokenCsrf = null; carregamentoToken = null }

httpClient.interceptors.request.use(async (configuracao: InternalAxiosRequestConfig) => {
  if (!metodosSeguros.has((configuracao.method ?? 'get').toLowerCase())) configuracao.headers.set('X-CSRF-TOKEN', await obterTokenCsrf())
  return configuracao
})

httpClient.interceptors.response.use(undefined, async erro => {
  const configuracao = erro.config as ConfiguracaoCsrf | undefined
  if (erro.response?.status === 403 && erro.response?.headers?.['x-csrf-error'] === '1' && configuracao && !configuracao._csrfRepetido) {
    configuracao._csrfRepetido = true; limparTokenCsrf(); configuracao.headers.set('X-CSRF-TOKEN', await obterTokenCsrf()); return httpClient(configuracao)
  }
  return Promise.reject(erro)
})
