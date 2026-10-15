<script setup lang="ts">
import { onMounted, ref } from 'vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Checkbox from 'primevue/checkbox'
import Select from 'primevue/select'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Paginator from 'primevue/paginator'
import Message from 'primevue/message'
import vTooltip from 'primevue/tooltip'
import type { UsuarioDto } from '../../../dto/usuario/UsuarioDto'
import { mensagemService, type Mensagem, type Ciencia } from '../../../services/mensagemService'
import { instituicaoService } from '../../../services/instituicaoService'

const props = defineProps<{ usuario: UsuarioDto }>()
defineEmits<{ voltar: [] }>()
const itens = ref<Mensagem[]>([]), total = ref(0), pagina = ref(0), busca = ref(''), ocupado = ref(false), erro = ref('')
const instituicoes = ref<{ id: number; nome: string }[]>([])
const dialog = ref(false), editando = ref<Mensagem | null>(null), renovando = ref(false)
const formulario = ref({ instituicaoId: props.usuario.instituicaoId, titulo: '', texto: '', inicio: '', fim: '', obrigatoria: true })
const excluirAlvo = ref<Mensagem | null>(null)
const historico = ref<Mensagem | null>(null), ciencias = ref<Ciencia[]>([]), totalCiencias = ref(0), paginaCiencias = ref(0)
const conteudo = ref<Ciencia | null>(null)
function local(iso: string): string { const d = new Date(iso); return new Date(d.getTime() - d.getTimezoneOffset()*60000).toISOString().slice(0,16) }
function data(iso: string): string { return new Date(iso).toLocaleString('pt-BR') }
function situacao(m: Mensagem): string { return m.excluida ? 'Excluída' : Date.now() < Date.parse(m.inicioEm) ? 'Agendada' : Date.now() >= Date.parse(m.fimEm) ? 'Encerrada' : 'Vigente' }
async function carregar(): Promise<void> {
  ocupado.value = true; erro.value = ''
  try { const r = await mensagemService.listar(pagina.value + 1, busca.value); itens.value = r.itens; total.value = r.total }
  catch { erro.value = 'Não foi possível carregar as mensagens.' }
  finally { ocupado.value = false }
}
function abrir(m: Mensagem | null = null, renovar = false): void {
  editando.value = m; renovando.value = renovar; erro.value = ''
  formulario.value = m ? { instituicaoId: m.instituicaoId, titulo: m.titulo, texto: m.texto, inicio: local(m.inicioEm), fim: local(m.fimEm), obrigatoria: m.obrigatoria }
    : { instituicaoId: props.usuario.instituicaoId, titulo: '', texto: '', inicio: local(new Date().toISOString()), fim: local(new Date(Date.now()+7*86400000).toISOString()), obrigatoria: true }
  dialog.value = true
}
async function salvar(): Promise<void> {
  const f = formulario.value
  if (!f.titulo.trim() || !f.texto.trim() || !f.inicio || !f.fim || new Date(f.fim) <= new Date(f.inicio)) { erro.value = 'Preencha título, texto e um período válido.'; return }
  ocupado.value = true; erro.value = ''
  try {
    await mensagemService.salvar(editando.value?.id ?? null, { instituicaoId: f.instituicaoId, titulo: f.titulo, texto: f.texto, inicioEm: new Date(f.inicio).toISOString(), fimEm: new Date(f.fim).toISOString(), obrigatoria: f.obrigatoria, versao: editando.value?.versao })
    dialog.value = false; await carregar()
  } catch { erro.value = 'Não foi possível salvar. Confira os campos; se outro administrador editou a mensagem, feche e recarregue a lista.' }
  finally { ocupado.value = false }
}
async function excluir(): Promise<void> {
  if (!excluirAlvo.value) return
  ocupado.value = true; erro.value = ''
  try { await mensagemService.excluir(excluirAlvo.value.id); excluirAlvo.value = null; await carregar() }
  catch { erro.value = 'Não foi possível excluir a mensagem.' }
  finally { ocupado.value = false }
}
async function carregarCiencias(): Promise<void> {
  if (!historico.value) return
  ocupado.value = true; erro.value = ''; ciencias.value = []
  try { const r = await mensagemService.ciencias(historico.value.id, paginaCiencias.value+1); ciencias.value = r.itens; totalCiencias.value = r.total }
  catch { erro.value = 'Não foi possível consultar as ciências.' }
  finally { ocupado.value = false }
}
function abrirCiencias(m: Mensagem): void { historico.value = m; paginaCiencias.value = 0; totalCiencias.value = 0; void carregarCiencias() }
onMounted(async () => {
  await carregar()
  if (!props.usuario.adminGlobal) { instituicoes.value = [{ id: props.usuario.instituicaoId, nome: props.usuario.instituicaoNome }]; return }
  try {
    let p = 1, quantidade = 0
    do { const r = await instituicaoService.listar('', p++, 100); instituicoes.value.push(...r.itens.filter(i => i.ativo)); quantidade += r.itens.length; if (!r.itens.length || quantidade >= r.total) break } while (true)
  } catch { erro.value = 'Não foi possível carregar as instituições.' }
})
</script>
<template>
  <section>
    <Button label="Voltar" icon="pi pi-arrow-left" text @click="$emit('voltar')" />
    <header class="cabecalho-crud"><div><h1>Mensagens aos usuários</h1><p>Publique avisos com vigência e acompanhe a ciência dos usuários.</p></div><Button label="Nova mensagem" icon="pi pi-plus" @click="abrir()" /></header>
    <Message v-if="erro" severity="error" :closable="false">{{ erro }}</Message>
    <form class="ferramentas-lista" @submit.prevent="pagina = 0; carregar()"><InputText v-model="busca" placeholder="Buscar por título" aria-label="Buscar mensagens" /><Button label="Buscar" type="submit" /><Button label="Atualizar" text @click="carregar" /></form>
    <DataTable :value="itens" :loading="ocupado" data-key="id" striped-rows scrollable>
      <template #empty>Nenhuma mensagem cadastrada.</template>
      <Column field="titulo" header="Título" /><Column field="instituicaoNome" header="Instituição" />
      <Column header="Vigência"><template #body="{ data: m }">{{ data(m.inicioEm) }} até {{ data(m.fimEm) }}</template></Column>
      <Column header="Obrigatória"><template #body="{ data: m }">{{ m.obrigatoria ? 'Sim' : 'Não' }}</template></Column>
      <Column header="Situação"><template #body="{ data: m }">{{ situacao(m) }}</template></Column>
      <Column header="Ações"><template #body="{ data: m }"><div class="acoes-mensagens">
        <Button v-tooltip.top="'Editar'" icon="pi pi-pencil" aria-label="Editar" size="small" text rounded :disabled="m.excluida || ocupado" @click="abrir(m)" />
        <Button v-tooltip.top="'Renovar validade'" icon="pi pi-calendar-plus" aria-label="Renovar validade" size="small" text rounded :disabled="m.excluida || ocupado" @click="abrir(m, true)" />
        <Button v-tooltip.top="'Ciências'" icon="pi pi-check-square" aria-label="Ciências" size="small" text rounded :disabled="ocupado" @click="abrirCiencias(m)" />
        <Button v-tooltip.top="'Excluir'" icon="pi pi-trash" aria-label="Excluir" size="small" severity="danger" text rounded :disabled="m.excluida || ocupado" @click="excluirAlvo = m" />
      </div></template></Column>
    </DataTable>
    <Paginator :first="pagina*20" :rows="20" :total-records="total" @page="pagina = $event.page; carregar()" />

    <Dialog v-model:visible="dialog" modal :closable="!ocupado" :header="renovando ? 'Renovar validade' : editando ? 'Editar mensagem' : 'Nova mensagem'" :style="{ width: '46rem', maxWidth: '95vw' }">
      <Message v-if="erro" severity="error" :closable="false">{{ erro }}</Message>
      <p v-if="editando">As ciências existentes serão preservadas. Para solicitar uma nova ciência, crie outra mensagem.</p>
      <form id="form-mensagem" class="form-mensagem" @submit.prevent="salvar">
        <label>Instituição<Select v-model="formulario.instituicaoId" :options="instituicoes" option-label="nome" option-value="id" :disabled="!!editando || !usuario.adminGlobal || ocupado" /></label>
        <label>Título<InputText v-model="formulario.titulo" required maxlength="150" :disabled="renovando || ocupado" /></label>
        <label>Texto<Textarea v-model="formulario.texto" required maxlength="20000" rows="8" :disabled="renovando || ocupado" /></label>
        <p>Datas e horários no fuso do seu navegador.</p>
        <label>Início<input v-model="formulario.inicio" type="datetime-local" required :disabled="ocupado" /></label>
        <label>Fim<input v-model="formulario.fim" type="datetime-local" required :disabled="ocupado" /></label>
        <label class="opcao-obrigatoria"><Checkbox v-model="formulario.obrigatoria" binary :disabled="renovando || ocupado" /> Ciência obrigatória</label>
      </form>
      <template #footer><Button label="Cancelar" severity="secondary" :disabled="ocupado" @click="dialog = false" /><Button label="Salvar" type="submit" form="form-mensagem" :loading="ocupado" /></template>
    </Dialog>
    <Dialog :visible="!!excluirAlvo" modal header="Excluir mensagem" :closable="!ocupado" @update:visible="$event || (excluirAlvo = null)">
      <p>Excluir “{{ excluirAlvo?.titulo }}”? Ela deixará de ser apresentada, mas o histórico de ciência será preservado.</p>
      <Message v-if="erro" severity="error">{{ erro }}</Message>
      <template #footer><Button label="Cancelar" :disabled="ocupado" @click="excluirAlvo = null" /><Button label="Excluir" severity="danger" :loading="ocupado" @click="excluir" /></template>
    </Dialog>
    <Dialog :visible="!!historico" modal :header="'Ciências — ' + (historico?.titulo ?? '')" :style="{ width: '55rem', maxWidth: '95vw' }" @update:visible="$event || (historico = null)">
      <Message v-if="erro" severity="error">{{ erro }}</Message>
      <DataTable :value="ciencias" :loading="ocupado" scrollable>
        <template #empty>Nenhum usuário deu ciência.</template>
        <Column field="nome" header="Usuário" /><Column field="email" header="E-mail" />
        <Column header="Data do aceite"><template #body="{ data: c }">{{ data(c.aceitoEm) }}</template></Column>
        <Column header="Conteúdo aceito"><template #body="{ data: c }"><Button :label="'Versão ' + c.conteudo.versao" text @click="conteudo = c" /></template></Column>
      </DataTable>
      <Paginator :first="paginaCiencias*20" :rows="20" :total-records="totalCiencias" @page="paginaCiencias = $event.page; carregarCiencias()" />
    </Dialog>
    <Dialog :visible="!!conteudo" modal header="Conteúdo no momento da ciência" :style="{ width: '42rem', maxWidth: '95vw' }" @update:visible="$event || (conteudo = null)">
      <template v-if="conteudo"><p>{{ conteudo.nome }} — {{ data(conteudo.aceitoEm) }}</p><h2>{{ conteudo.conteudo.titulo }}</h2><div class="texto-aceito">{{ conteudo.conteudo.texto }}</div></template>
    </Dialog>
  </section>
</template>
<style scoped>
.cabecalho-crud { margin: 1rem 0 1.5rem; align-items: center; }
.cabecalho-crud p { margin-top: .75rem; line-height: 1.5; }
.cabecalho-crud > .p-button { flex-shrink: 0; }
.acoes-mensagens { display: flex; align-items: center; gap: .25rem; flex-wrap: nowrap; }
.form-mensagem { display: grid; gap: 1rem; }
.form-mensagem label { display: grid; gap: .4rem; }
.form-mensagem .opcao-obrigatoria { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
input[type=datetime-local] { padding: .6rem; border: 1px solid #cbd5e1; border-radius: .4rem; font: inherit; }
.texto-aceito { white-space: pre-wrap; overflow-wrap: anywhere; }
</style>
