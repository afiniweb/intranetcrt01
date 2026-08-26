<script setup lang="ts">
import { onMounted, ref } from 'vue'
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable, { type DataTablePageEvent } from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import InputSwitch from 'primevue/inputswitch'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Tag from 'primevue/tag'
import type { InstituicaoDto, SalvarInstituicaoDto } from '../../../dto/instituicao/InstituicaoDto'
import { instituicaoService } from '../../../services/instituicaoService'

const emit = defineEmits<{ voltar: [] }>()
const instituicoes = ref<InstituicaoDto[]>([])
const total = ref(0)
const pagina = ref(1)
const porPagina = ref(10)
const busca = ref('')
const loading = ref(false)
const saving = ref(false)
const erro = ref('')
const dialogForm = ref(false)
const dialogExclusao = ref(false)
const instituicaoAtual = ref<InstituicaoDto | null>(null)
const formulario = ref<SalvarInstituicaoDto>({ nome: '', sigla: '', cnpj: null, ativo: true })

async function carregar(): Promise<void> {
  loading.value = true; erro.value = ''
  try {
    const response = await instituicaoService.listar(busca.value, pagina.value, porPagina.value)
    instituicoes.value = response.itens; total.value = response.total
  } catch { erro.value = 'Não foi possível carregar as instituições.' }
  finally { loading.value = false }
}
function abrirNovo(): void {
  instituicaoAtual.value = null
  formulario.value = { nome: '', sigla: '', cnpj: null, ativo: true }
  dialogForm.value = true
}
function abrirEdicao(item: InstituicaoDto): void {
  instituicaoAtual.value = item
  formulario.value = { nome: item.nome, sigla: item.sigla, cnpj: item.cnpj, ativo: item.ativo }
  dialogForm.value = true
}
async function salvar(): Promise<void> {
  if (!formulario.value.nome.trim() || !formulario.value.sigla.trim()) { erro.value = 'Informe nome e sigla.'; return }
  saving.value = true; erro.value = ''
  try {
    if (instituicaoAtual.value) await instituicaoService.atualizar(instituicaoAtual.value.id, formulario.value)
    else await instituicaoService.criar(formulario.value)
    dialogForm.value = false; await carregar()
  } catch { erro.value = 'Não foi possível salvar a instituição. Verifique os dados informados.' }
  finally { saving.value = false }
}
function confirmarExclusao(item: InstituicaoDto): void { instituicaoAtual.value = item; dialogExclusao.value = true }
async function excluir(): Promise<void> {
  if (!instituicaoAtual.value) return
  saving.value = true
  try { await instituicaoService.excluir(instituicaoAtual.value.id); dialogExclusao.value = false; await carregar() }
  catch { erro.value = 'Não foi possível inativar a instituição.' }
  finally { saving.value = false }
}
function mudarPagina(event: DataTablePageEvent): void { pagina.value = event.page + 1; porPagina.value = event.rows; void carregar() }
function pesquisar(): void { pagina.value = 1; void carregar() }
function formatarCnpj(cnpj: string | null): string {
  if (!cnpj) return 'Não informado'
  return cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5')
}
onMounted(carregar)
</script>

<template>
  <section class="pagina-crud">
    <div class="barra-voltar"><Button label="Voltar" icon="pi pi-arrow-left" text @click="emit('voltar')" /></div>
    <header class="cabecalho-crud">
      <div><p class="identificador-secao">CADASTROS</p><h1>Instituições</h1><p>Gerencie as instituições que utilizam a plataforma.</p></div>
      <Button label="Nova instituição" icon="pi pi-plus" @click="abrirNovo" />
    </header>
    <Message v-if="erro" severity="error" closable @close="erro = ''">{{ erro }}</Message>
    <div class="ferramentas-lista">
      <InputText v-model="busca" placeholder="Buscar por nome ou sigla" @keyup.enter="pesquisar" />
      <Button label="Buscar" icon="pi pi-search" severity="secondary" @click="pesquisar" />
    </div>
    <DataTable :value="instituicoes" :loading="loading" lazy paginator :rows="porPagina" :total-records="total" data-key="id" striped-rows @page="mudarPagina">
      <template #empty>Nenhuma instituição cadastrada.</template>
      <Column field="nome" header="Nome" />
      <Column field="sigla" header="Sigla" />
      <Column header="CNPJ"><template #body="{ data }">{{ formatarCnpj(data.cnpj) }}</template></Column>
      <Column header="Situação"><template #body="{ data }"><Tag :value="data.ativo ? 'Ativa' : 'Inativa'" :severity="data.ativo ? 'success' : 'secondary'" /></template></Column>
      <Column header="Ações" body-class="acoes-tabela">
        <template #body="{ data }">
          <Button icon="pi pi-pencil" text rounded aria-label="Editar instituição" @click="abrirEdicao(data)" />
          <Button icon="pi pi-trash" text rounded severity="danger" aria-label="Excluir instituição" @click="confirmarExclusao(data)" />
        </template>
      </Column>
    </DataTable>

    <Dialog v-model:visible="dialogForm" modal :header="instituicaoAtual ? 'Editar instituição' : 'Nova instituição'" :style="{ width: 'min(32rem, 95vw)' }">
      <form class="formulario-dialog" @submit.prevent="salvar">
        <label>Nome <InputText v-model="formulario.nome" maxlength="150" required autofocus /></label>
        <label>Sigla <InputText v-model="formulario.sigla" maxlength="20" required /></label>
        <label>CNPJ <InputText v-model="formulario.cnpj" maxlength="18" placeholder="00.000.000/0000-00" /></label>
        <label class="campo-switch"><InputSwitch v-model="formulario.ativo" /><span>Instituição ativa</span></label>
      </form>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="dialogForm = false" />
        <Button label="Salvar" icon="pi pi-check" :loading="saving" @click="salvar" />
      </template>
    </Dialog>

    <Dialog v-model:visible="dialogExclusao" modal header="Inativar instituição" :style="{ width: 'min(28rem, 95vw)' }">
      <p>Deseja inativar <strong>{{ instituicaoAtual?.nome }}</strong>? O histórico será preservado.</p>
      <template #footer>
        <Button label="Cancelar" severity="secondary" text @click="dialogExclusao = false" />
        <Button label="Inativar" icon="pi pi-trash" severity="danger" :loading="saving" @click="excluir" />
      </template>
    </Dialog>
  </section>
</template>
