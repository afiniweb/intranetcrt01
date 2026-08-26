<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable, { type DataTablePageEvent } from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import InputSwitch from 'primevue/inputswitch'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Password from 'primevue/password'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import type { EscritorioDto } from '../../../dto/escritorio/EscritorioDto'
import type { InstituicaoDto } from '../../../dto/instituicao/InstituicaoDto'
import type { PerfilUsuario, SalvarUsuarioDto, UsuarioDto } from '../../../dto/usuario/UsuarioDto'
import { escritorioService } from '../../../services/escritorioService'
import { instituicaoService } from '../../../services/instituicaoService'
import { usuarioService } from '../../../services/usuarioService'

const emit = defineEmits<{ voltar: [] }>()
const usuarios = ref<UsuarioDto[]>([]), instituicoes = ref<InstituicaoDto[]>([]), escritorios = ref<EscritorioDto[]>([])
const total = ref(0), pagina = ref(1), porPagina = ref(10), busca = ref('')
const loading = ref(false), saving = ref(false), erro = ref('')
const dialogForm = ref(false), dialogExclusao = ref(false), usuarioAtual = ref<UsuarioDto | null>(null)
const formulario = ref<SalvarUsuarioDto>({ instituicaoId: 0, escritorioId: 0, nome: '', email: '', perfil: 'PUBLICADOR', adminGlobal: false, senha: null, ativo: true })
const perfis: { label: string; value: PerfilUsuario }[] = [{ label: 'Admin', value: 'ADMIN' }, { label: 'Publicador', value: 'PUBLICADOR' }]
const escritoriosDisponiveis = computed(() => escritorios.value.filter((item) => item.instituicaoId === formulario.value.instituicaoId && item.ativo))

async function carregar(): Promise<void> { loading.value = true; erro.value = ''; try { const response = await usuarioService.listar(busca.value, pagina.value, porPagina.value); usuarios.value = response.itens; total.value = response.total } catch { erro.value = 'Não foi possível carregar os usuários.' } finally { loading.value = false } }
async function carregarDependencias(): Promise<void> { try { const [a, b] = await Promise.all([instituicaoService.listar('', 1, 100), escritorioService.listar('', 1, 100)]); instituicoes.value = a.itens.filter((item) => item.ativo); escritorios.value = b.itens } catch { erro.value = 'Não foi possível carregar instituições e escritórios.' } }
function abrirNovo(): void { usuarioAtual.value = null; const instituicaoId = instituicoes.value[0]?.id ?? 0; formulario.value = { instituicaoId, escritorioId: escritorios.value.find((item) => item.instituicaoId === instituicaoId && item.ativo)?.id ?? 0, nome: '', email: '', perfil: 'PUBLICADOR', adminGlobal: false, senha: '', ativo: true }; dialogForm.value = true }
function abrirEdicao(item: UsuarioDto): void { usuarioAtual.value = item; formulario.value = { instituicaoId: item.instituicaoId, escritorioId: item.escritorioId, nome: item.nome, email: item.email, perfil: item.perfil, adminGlobal: item.adminGlobal, senha: null, ativo: item.ativo }; dialogForm.value = true }
async function salvar(): Promise<void> {
  if (!formulario.value.nome.trim() || !formulario.value.email.trim() || !formulario.value.instituicaoId || !formulario.value.escritorioId) { erro.value = 'Preencha nome, e-mail, instituição e escritório.'; return }
  if (!usuarioAtual.value && (!formulario.value.senha || formulario.value.senha.length < 8)) { erro.value = 'Informe uma senha inicial com pelo menos 8 caracteres.'; return }
  saving.value = true; erro.value = ''
  try { if (usuarioAtual.value) await usuarioService.atualizar(usuarioAtual.value.id, formulario.value); else await usuarioService.criar(formulario.value); dialogForm.value = false; await carregar() }
  catch { erro.value = 'Não foi possível salvar o usuário. Verifique os dados e se o e-mail já está cadastrado.' } finally { saving.value = false }
}
function confirmarExclusao(item: UsuarioDto): void { usuarioAtual.value = item; dialogExclusao.value = true }
async function excluir(): Promise<void> { if (!usuarioAtual.value) return; saving.value = true; try { await usuarioService.excluir(usuarioAtual.value.id); dialogExclusao.value = false; await carregar() } catch { erro.value = 'Não foi possível inativar o usuário.' } finally { saving.value = false } }
function mudarPagina(event: DataTablePageEvent): void { pagina.value = event.page + 1; porPagina.value = event.rows; void carregar() }
function pesquisar(): void { pagina.value = 1; void carregar() }
watch(() => formulario.value.instituicaoId, () => { if (!escritoriosDisponiveis.value.some((item) => item.id === formulario.value.escritorioId)) formulario.value.escritorioId = escritoriosDisponiveis.value[0]?.id ?? 0 })
watch(() => formulario.value.perfil, (perfil) => { if (perfil !== 'ADMIN') formulario.value.adminGlobal = false })
onMounted(async () => { await Promise.all([carregar(), carregarDependencias()]) })
</script>

<template>
  <section class="pagina-crud">
    <div class="barra-voltar"><Button label="Voltar" icon="pi pi-arrow-left" text @click="emit('voltar')" /></div>
    <header class="cabecalho-crud"><div><p class="identificador-secao">CADASTROS</p><h1>Usuários</h1><p>Gerencie acessos, perfis e remanejamentos entre escritórios.</p></div><Button label="Novo usuário" icon="pi pi-plus" :disabled="!escritorios.length" @click="abrirNovo" /></header>
    <Message v-if="erro" severity="error" closable @close="erro = ''">{{ erro }}</Message>
    <Message v-if="!escritorios.length && !loading" severity="warn">Cadastre um escritório ativo antes de incluir usuários.</Message>
    <div class="ferramentas-lista"><InputText v-model="busca" placeholder="Buscar por nome, e-mail ou escritório" @keyup.enter="pesquisar" /><Button label="Buscar" icon="pi pi-search" severity="secondary" @click="pesquisar" /></div>
    <DataTable :value="usuarios" :loading="loading" lazy paginator :rows="porPagina" :total-records="total" data-key="id" striped-rows @page="mudarPagina">
      <template #empty>Nenhum usuário cadastrado.</template>
      <Column field="nome" header="Nome"><template #body="{ data }"><div class="celula-principal"><strong>{{ data.nome }}</strong><span>{{ data.email }}</span></div></template></Column>
      <Column field="instituicaoNome" header="Instituição" /><Column field="escritorioNome" header="Escritório" />
      <Column header="Perfil"><template #body="{ data }"><Tag :value="data.perfil === 'ADMIN' ? (data.adminGlobal ? 'Admin global' : 'Admin') : 'Publicador'" :severity="data.perfil === 'ADMIN' ? 'info' : 'secondary'" /></template></Column>
      <Column header="Situação"><template #body="{ data }"><Tag :value="data.ativo ? 'Ativo' : 'Inativo'" :severity="data.ativo ? 'success' : 'secondary'" /></template></Column>
      <Column header="Ações" body-class="acoes-tabela"><template #body="{ data }"><Button icon="pi pi-pencil" text rounded aria-label="Editar usuário" @click="abrirEdicao(data)" /><Button icon="pi pi-trash" text rounded severity="danger" aria-label="Excluir usuário" @click="confirmarExclusao(data)" /></template></Column>
    </DataTable>
    <Dialog v-model:visible="dialogForm" modal :header="usuarioAtual ? 'Editar usuário' : 'Novo usuário'" :style="{ width: 'min(42rem, 95vw)' }">
      <form class="formulario-dialog" @submit.prevent="salvar">
        <div class="linha-formulario linha-formulario-igual"><label>Nome <InputText v-model="formulario.nome" maxlength="150" required /></label><label>E-mail <InputText v-model="formulario.email" maxlength="180" type="email" required /></label></div>
        <div class="linha-formulario linha-formulario-igual"><label>Instituição <Select v-model="formulario.instituicaoId" :options="instituicoes" option-label="nome" option-value="id" filter /></label><label>Escritório <Select v-model="formulario.escritorioId" :options="escritoriosDisponiveis" option-label="nome" option-value="id" filter /></label></div>
        <div class="linha-formulario linha-formulario-igual"><label>Perfil <Select v-model="formulario.perfil" :options="perfis" option-label="label" option-value="value" /></label><label>{{ usuarioAtual ? 'Nova senha (opcional)' : 'Senha inicial' }} <Password v-model="formulario.senha" :feedback="!usuarioAtual" toggle-mask /></label></div>
        <div class="opcoes-switch"><label class="campo-switch"><InputSwitch v-model="formulario.ativo" /><span>Usuário ativo</span></label><label v-if="formulario.perfil === 'ADMIN'" class="campo-switch"><InputSwitch v-model="formulario.adminGlobal" /><span>Administrador global</span></label></div>
      </form>
      <template #footer><Button label="Cancelar" severity="secondary" text @click="dialogForm = false" /><Button label="Salvar" icon="pi pi-check" :loading="saving" @click="salvar" /></template>
    </Dialog>
    <Dialog v-model:visible="dialogExclusao" modal header="Inativar usuário" :style="{ width: 'min(28rem, 95vw)' }"><p>Deseja inativar <strong>{{ usuarioAtual?.nome }}</strong>? O histórico e as publicações serão preservados.</p><template #footer><Button label="Cancelar" severity="secondary" text @click="dialogExclusao = false" /><Button label="Inativar" icon="pi pi-trash" severity="danger" :loading="saving" @click="excluir" /></template></Dialog>
  </section>
</template>
