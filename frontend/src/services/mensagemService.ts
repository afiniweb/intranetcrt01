import { httpClient } from './httpClient'
export interface Mensagem {
  id: number; instituicaoId: number; instituicaoNome: string; titulo: string; texto: string
  inicioEm: string; fimEm: string; obrigatoria: boolean; excluida: boolean; versao: number
}
export interface Ciencia { usuarioId: number; nome: string; email: string; aceitoEm: string; conteudo: Mensagem }
export type SalvarMensagem = Pick<Mensagem, 'instituicaoId' | 'titulo' | 'texto' | 'inicioEm' | 'fimEm' | 'obrigatoria'> & { versao?: number }
export const mensagemService = {
  async listar(pagina = 1, busca = '') { return (await httpClient.get<{ itens: Mensagem[]; total: number }>('/mensagens', { params: { pagina, busca } })).data },
  async salvar(id: number | null, dados: SalvarMensagem) { return id === null ? httpClient.post('/mensagens', dados) : httpClient.put(`/mensagens/${id}`, dados) },
  async excluir(id: number) { await httpClient.delete(`/mensagens/${id}`) },
  async ciencias(id: number, pagina = 1) { return (await httpClient.get<{ itens: Ciencia[]; total: number }>(`/mensagens/${id}/ciencias`, { params: { pagina } })).data },
  async pendente() { return (await httpClient.get<{ mensagem: Mensagem | null }>('/mensagens/pendente')).data.mensagem },
  async apresentar(id: number) { return (await httpClient.post<{ mensagem: Mensagem; restantes: number }>(`/mensagens/${id}/apresentar`)).data },
  async responder(m: Mensagem, aceitar: boolean) { await httpClient.post(`/mensagens/${m.id}/responder`, { aceitar, versao: m.versao }) },
}
