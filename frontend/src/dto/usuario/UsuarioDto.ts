export type PerfilUsuario = 'ADMIN' | 'PUBLICADOR'
export interface UsuarioDto {
  id: number; instituicaoId: number; instituicaoNome: string; escritorioId: number; escritorioNome: string
  nome: string; email: string; perfil: PerfilUsuario; adminGlobal: boolean; ativo: boolean; criadoEm: string; atualizadoEm: string
}
export interface SalvarUsuarioDto { instituicaoId: number; escritorioId: number; nome: string; email: string; perfil: PerfilUsuario; adminGlobal: boolean; senha: string | null; ativo: boolean }
export interface ListaUsuariosDto { itens: UsuarioDto[]; total: number; pagina: number; porPagina: number }
