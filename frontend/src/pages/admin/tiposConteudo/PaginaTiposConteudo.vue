<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable, { type DataTablePageEvent } from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import InputSwitch from 'primevue/inputswitch'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'
import type { InstituicaoDto } from '../../../dto/instituicao/InstituicaoDto'
import type { SalvarTipoConteudoDto, TipoConteudoDto } from '../../../dto/tipoConteudo/TipoConteudoDto'
import type { UsuarioDto } from '../../../dto/usuario/UsuarioDto'
import { instituicaoService } from '../../../services/instituicaoService'
import { tipoConteudoService } from '../../../services/tipoConteudoService'
import { usuarioService } from '../../../services/usuarioService'

const emit = defineEmits<{ voltar: [] }>()
const tipos = ref<TipoConteudoDto[]>([]), instituicoes = ref<InstituicaoDto[]>([]), usuarios = ref<UsuarioDto[]>([])
const total = ref(0), pagina = ref(1), porPagina = ref(10), busca = ref('')
const loading = ref(false), saving = ref(false), erro = ref('')
const dialogForm = ref(false), dialogExclusao = ref(false), tipoAtual = ref<TipoConteudoDto | null>(null)
const formulario = ref<SalvarTipoConteudoDto>({ instituicaoId: 0, responsavelId: 0, nome: '', descricao: null, ativo: true })
const publicadoresDisponiveis = computed(() => usuarios.value.filter((item) => item.instituicaoId === formulario.value.instituicaoId && item.perfil === 'PUBLICADOR' && item.ativo))

async function carregar(): Promise<void> { loading.value = true; erro.value = ''; try { const response = await tipoConteudoService.listar(busca.value, pagina.value, porPagina.value); tipos.value = response.itens; total.value = response.total } catch { erro.value = 'Não foi possível carregar os tipos de conteúdo.' } finally { loading.value = false } }
async function carregarDependencias(): Promise<void> { try { const [a, b] = await Promise.all([instituicaoService.listar('', 1, 100), usuarioService.listar('', 1, 100)]); instituicoes.value = a.itens.filter((item) => item.ativo); usuarios.value = b.itens } catch { erro.value = 'Não foi possível carregar instituições e Publicadores.' } }
function abrirNovo(): void { tipoAtual.value = null; const instituicaoId = instituicoes.value[0]?.id ?? 0; formulario.value = { instituicaoId, responsavelId: usuarios.value.find((item) => item.instituicaoId === instituicaoId && item.perfil === 'PUBLICADOR' && item.ativo)?.id ?? 0, nome: '', descricao: null, ativo: true }; dialogForm.value = true }
function abrirEdicao(item: TipoConteudoDto): void { tipoAtual.value = item; formulario.value = { instituicaoId: item.instituicaoId, responsavelId: item.responsavelId, nome: item.nome, descricao: item.descricao, ativo: item.ativo }; dialogForm.value = true }
async function salvar(): Promise<void> {
  if (!formulario.value.nome.trim() || !formulario.value.instituicaoId || !formulario.value.responsavelId) { erro.value = 'Preencha nome, instituição e Publicador responsável.'; return }
  saving.value = true; erro.value = ''
  try { if (tipoAtual.value) await tipoConteudoService.atualizar(tipoAtual.value.id, formulario.value); else await tipoConteudoService.criar(formulario.value); dialogForm.value = false; await carregar() }
  catch { erro.value = 'Não foi possível salvar o tipo de conteúdo. Verifique o nome e o responsável selecionado.' } finally { saving.value = false }
}
function confirmarExclusao(item: TipoConteudoDto): void { tipoAtual.value = item; dialogExclusao.value = true }
async function excluir(): Promise<void> { if (!tipoAtual.value) return; saving.value = true; try { await tipoConteudoService.excluir(tipoAtual.value.id); dialogExclusao.value = false; await carregar() } catch { erro.value = 'Não foi possível inativar o tipo de conteúdo.' } finally { saving.value = false } }
function mudarPagina(event: DataTablePageEvent): void { pagina.value = event.page + 1; porPagina.value = event.rows; void carregar() }
function pesquisar(): void { pagina.value = 1; void carregar() }
watch(() => formulario.value.instituicaoId, () => { if (!publicadoresDisponiveis.value.some((item) => item.id === formulario.value.responsavelId)) formulario.value.responsavelId = publicadoresDisponiveis.value[0]?.id ?? 0 })
onMounted(async () => { await Promise.all([carregar(), carregarDependencias()]) })
</script>

<template>
  <section class="pagina-crud">
    <div class="barra-voltar"><Button label="Voltar" icon="pi pi-arrow-left" text @click="emit('voltar')" /></div>
    <header class="cabecalho-crud"><div><p class="identificador-secao">CADASTROS</p><h1>Tipos de conteúdo</h1><p>Defina categorias editoriais e seus Publicadores responsáveis.</p></div><Button label="Novo tipo" icon="pi pi-plus" :disabled="!usuarios.some((item) => item.perfil === 'PUBLICADOR' && item.ativo)" @click="abrirNovo" /></header>
    <Message v-if="erro" severity="error" closable @close="erro = ''">{{ erro }}</Message>
    <Message v-if="!usuarios.some((item) => item.perfil === 'PUBLICADOR' && item.ativo) && !loading" severity="warn">Cadastre um usuário ativo com perfil Publicador antes de incluir tipos de conteúdo.</Message>
    <div class="ferramentas-lista"><InputText v-model="busca" placeholder="Buscar por nome, instituição ou responsável" @keyup.enter="pesquisar" /><Button label="Buscar" icon="pi pi-search" severity="secondary" @click="pesquisar" /></div>
    <DataTable :value="tipos" :loading="loading" lazy paginator :rows="porPagina" :total-records="total" data-key="id" striped-rows @page="mudarPagina">
      <template #empty>Nenhum tipo de conteúdo cadastrado.</template>
      <Column field="nome" header="Tipo"><template #body="{ data }"><div class="celula-principal"><strong>{{ data.nome }}</strong><span>{{ data.descricao || 'Sem descrição' }}</span></div></template></Column>
      <Column field="instituicaoNome" header="Instituição" /><Column field="responsavelNome" header="Publicador responsável" />
      <Column header="Fluxo"><template #body><Tag value="Aprovação obrigatória" severity="info" /></template></Column>
      <Column header="Situação"><template #body="{ data }"><Tag :value="data.ativo ? 'Ativo' : 'Inativo'" :severity="data.ativo ? 'success' : 'secondary'" /></template></Column>
      <Column header="Ações" body-class="acoes-tabela"><template #body="{ data }"><Button icon="pi pi-pencil" text rounded aria-label="Editar tipo de conteúdo" @click="abrirEdicao(data)" /><Button icon="pi pi-trash" text rounded severity="danger" aria-label="Excluir tipo de conteúdo" @click="confirmarExclusao(data)" /></template></Column>
    </DataTable>
    <Dialog v-model:visible="dialogForm" modal :header="tipoAtual ? 'Editar tipo de conteúdo' : 'Novo tipo de conteúdo'" :style="{ width: 'min(38rem, 95vw)' }">
      <form class="formulario-dialog" @submit.prevent="salvar">
        <label>Nome <InputText v-model="formulario.nome" maxlength="150" required /></label>
        <label>Instituição <Select v-model="formulario.instituicaoId" :options="instituicoes" option-label="nome" option-value="id" filter /></label>
        <label>Publicador responsável <Select v-model="formulario.responsavelId" :options="publicadoresDisponiveis" option-label="nome" option-value="id" filter placeholder="Selecione" /><small v-if="!publicadoresDisponiveis.length">Não há Publicadores ativos nesta instituição.</small></label>
        <label>Descrição <Textarea v-model="formulario.descricao" maxlength="500" rows="4" auto-resize /></label>
        <label class="campo-switch"><InputSwitch v-model="formulario.ativo" /><span>Tipo de conteúdo ativo</span></label>
        <Message severity="info" :closable="false">As publicações deste tipo serão disponibilizadas imediatamente pelo Publicador responsável.</Message>
      </form>
      <template #footer><Button label="Cancelar" severity="secondary" text @click="dialogForm = false" /><Button label="Salvar" icon="pi pi-check" :loading="saving" :disabled="!formulario.responsavelId" @click="salvar" /></template>
    </Dialog>
    <Dialog v-model:visible="dialogExclusao" modal header="Inativar tipo de conteúdo" :style="{ width: 'min(28rem, 95vw)' }"><p>Deseja inativar <strong>{{ tipoAtual?.nome }}</strong>? As publicações e o histórico serão preservados.</p><template #footer><Button label="Cancelar" severity="secondary" text @click="dialogExclusao = false" /><Button label="Inativar" icon="pi pi-trash" severity="danger" :loading="saving" @click="excluir" /></template></Dialog>
  </section>
</template>
