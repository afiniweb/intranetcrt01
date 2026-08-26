export type EscopoParametro = 'GLOBAL' | 'INSTITUICAO'
export interface ParametroSistemaDto { id: number; escopo: EscopoParametro; instituicaoId: number | null; instituicaoNome: string | null; limiteUploadMb: number; notificacaoInterna: boolean; notificacaoEmail: boolean; antecedenciaExpiracaoDias: number; fusoHorario: string; atualizadoEm: string }
export interface SalvarParametroSistemaDto { instituicaoId: number | null; limiteUploadMb: number; notificacaoInterna: boolean; notificacaoEmail: boolean; antecedenciaExpiracaoDias: number; fusoHorario: string }
