import type { ParametroSistemaDto, SalvarParametroSistemaDto } from '../dto/parametroSistema/ParametroSistemaDto'
import { httpClient } from './httpClient'
export const parametroSistemaService = {
  async listar(): Promise<ParametroSistemaDto[]> { return (await httpClient.get<ParametroSistemaDto[]>('/parametros')).data },
  async criar(dados: SalvarParametroSistemaDto): Promise<ParametroSistemaDto> { return (await httpClient.post<ParametroSistemaDto>('/parametros', dados)).data },
  async atualizar(id: number, dados: SalvarParametroSistemaDto): Promise<ParametroSistemaDto> { return (await httpClient.put<ParametroSistemaDto>(`/parametros/${id}`, dados)).data },
}
