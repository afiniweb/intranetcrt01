<script setup lang="ts">
import { onMounted, ref } from 'vue'
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable, { type DataTablePageEvent } from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import InputSwitch from 'primevue/inputswitch'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import type { EscritorioDto, SalvarEscritorioDto } from '../../../dto/escritorio/EscritorioDto'
import type { InstituicaoDto } from '../../../dto/instituicao/InstituicaoDto'
import { escritorioService } from '../../../services/escritorioService'
import { instituicaoService } from '../../../services/instituicaoService'

const emit = defineEmits<{ voltar: [] }>()
const escritorios = ref<EscritorioDto[]>([])
const instituicoes = ref<InstituicaoDto[]>([])
const total = ref(0), pagina = ref(1), porPagina = ref(10)
const busca = ref(''), loading = ref(false), saving = ref(false), erro = ref('')
const dialogForm = ref(false), dialogExclusao = ref(false)
const escritorioAtual = ref<EscritorioDto | null>(null)
const formulario = ref<SalvarEscritorioDto>({ instituicaoId: 0, nome: '', uf: '', cidade: '', endereco: null, ativo: true })
const estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO']

async function carregar(): Promise<void> {
  loading.value = true; erro.value = ''
  try { const response = await escritorioService.listar(busca.value, pagina.value, porPagina.value); escritorios.value = response.itens; total.value = response.total }
  catch { erro.value = 'Não foi possível carregar os escritórios.' }
  finally { loading.value = false }
}
async function carregarInstituicoes(): Promise<void> {
  try { instituicoes.value = (await instituicaoService.listar('', 1, 100)).itens.filter((item) => item.ativo) }
  catch { erro.value = 'Não foi possível carregar as instituições.' }
}
function abrirNovo(): void { escritorioAtual.value = null; formulario.value = { instituicaoId: instituicoes.value[0]?.id ?? 0, nome: '', uf: '', cidade: '', endereco: null, ativo: true }; dialogForm.value = true }
function abrirEdicao(item: EscritorioDto): void { escritorioAtual.value = item; formulario.value = { instituicaoId: item.instituicaoId, nome: item.nome, uf: item.uf, cidade: item.cidade, endereco: item.endereco, ativo: item.ativo }; dialogForm.value = true }
async function salvar(): Promise<void> {
  if (!formulario.value.instituicaoId || !formulario.value.nome.trim() || !formulario.value.uf || !formulario.value.cidade.trim()) { erro.value = 'Informe instituição, nome, UF e cidade.'; return }
  saving.value = true; erro.value = ''
  try { if (escritorioAtual.value) await escritorioService.atualizar(escritorioAtual.value.id, formulario.value); else await escritorioService.criar(formulario.value); dialogForm.value = false; await carregar() }
  catch { erro.value = 'Não foi possível salvar o escritório. Verifique os dados informados.' }
  finally { saving.value = false }
}
function confirmarExclusao(item: EscritorioDto): void { escritorioAtual.value = item; dialogExclusao.value = true }
async function excluir(): Promise<void> { if (!escritorioAtual.value) return; saving.value = true; try { await escritorioService.excluir(escritorioAtual.value.id); dialogExclusao.value = false; await carregar() } catch { erro.value = 'Não foi possível inativar o escritório.' } finally { saving.value = false } }
function mudarPagina(event: DataTablePageEvent): void { pagina.value = event.page + 1; porPagina.value = event.rows; void carregar() }
function pesquisar(): void { pagina.value = 1; void carregar() }
onMounted(async () => { await Promise.all([carregar(), carregarInstituicoes()]) })
</script>

<template>
  <section class="pagina-crud">
    <div class="barra-voltar"><Button label="Voltar" icon="pi pi-arrow-left" text @click="emit('voltar')" /></div>
    <header class="cabecalho-crud"><div><p class="identificador-secao">CADASTROS</p><h1>Escritórios regionais</h1><p>Organize os escritórios por instituição e localização.</p></div><Button label="Novo escritório" icon="pi pi-plus" :disabled="!instituicoes.length" @click="abrirNovo" /></header>
    <Message v-if="erro" severity="error" closable @close="erro = ''">{{ erro }}</Message>
    <Message v-if="!instituicoes.length && !loading" severity="warn">Cadastre uma instituição ativa antes de incluir escritórios.</Message>
    <div class="ferramentas-lista"><InputText v-model="busca" placeholder="Buscar por nome, cidade ou instituição" @keyup.enter="pesquisar" /><Button label="Buscar" icon="pi pi-search" severity="secondary" @click="pesquisar" /></div>
    <DataTable :value="escritorios" :loading="loading" lazy paginator :rows="porPagina" :total-records="total" data-key="id" striped-rows @page="mudarPagina">
      <template #empty>Nenhum escritório cadastrado.</template>
      <Column field="nome" header="Nome" /><Column field="instituicaoNome" header="Instituição" /><Column field="cidade" header="Cidade" /><Column field="uf" header="UF" />
      <Column header="Situação"><template #body="{ data }"><Tag :value="data.ativo ? 'Ativo' : 'Inativo'" :severity="data.ativo ? 'success' : 'secondary'" /></template></Column>
      <Column header="Ações" body-class="acoes-tabela"><template #body="{ data }"><Button icon="pi pi-pencil" text rounded aria-label="Editar escritório" @click="abrirEdicao(data)" /><Button icon="pi pi-trash" text rounded severity="danger" aria-label="Excluir escritório" @click="confirmarExclusao(data)" /></template></Column>
    </DataTable>
    <Dialog v-model:visible="dialogForm" modal :header="escritorioAtual ? 'Editar escritório' : 'Novo escritório'" :style="{ width: 'min(36rem, 95vw)' }">
      <form class="formulario-dialog" @submit.prevent="salvar">
        <label>Instituição <Select v-model="formulario.instituicaoId" :options="instituicoes" option-label="nome" option-value="id" placeholder="Selecione" filter /></label>
        <label>Nome <InputText v-model="formulario.nome" maxlength="150" required /></label>
        <div class="linha-formulario"><label>UF <Select v-model="formulario.uf" :options="estados" placeholder="Selecione" /></label><label>Cidade <InputText v-model="formulario.cidade" maxlength="120" required /></label></div>
        <label>Endereço <InputText v-model="formulario.endereco" maxlength="255" /></label>
        <label class="campo-switch"><InputSwitch v-model="formulario.ativo" /><span>Escritório ativo</span></label>
      </form>
      <template #footer><Button label="Cancelar" severity="secondary" text @click="dialogForm = false" /><Button label="Salvar" icon="pi pi-check" :loading="saving" @click="salvar" /></template>
    </Dialog>
    <Dialog v-model:visible="dialogExclusao" modal header="Inativar escritório" :style="{ width: 'min(28rem, 95vw)' }"><p>Deseja inativar <strong>{{ escritorioAtual?.nome }}</strong>? O histórico será preservado.</p><template #footer><Button label="Cancelar" severity="secondary" text @click="dialogExclusao = false" /><Button label="Inativar" icon="pi pi-trash" severity="danger" :loading="saving" @click="excluir" /></template></Dialog>
  </section>
</template>
