<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import InputSwitch from 'primevue/inputswitch'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import type { InstituicaoDto } from '../../../dto/instituicao/InstituicaoDto'
import type { ParametroSistemaDto, SalvarParametroSistemaDto } from '../../../dto/parametroSistema/ParametroSistemaDto'
import type { UsuarioDto } from '../../../dto/usuario/UsuarioDto'
import { instituicaoService } from '../../../services/instituicaoService'
import { parametroSistemaService } from '../../../services/parametroSistemaService'

const props = defineProps<{ usuario: UsuarioDto }>()
const emit = defineEmits<{ voltar: [] }>()
const parametros = ref<ParametroSistemaDto[]>([]), instituicoes = ref<InstituicaoDto[]>([])
const loading = ref(false), saving = ref(false), erro = ref(''), sucesso = ref('')
const dialogForm = ref(false), parametroAtual = ref<ParametroSistemaDto | null>(null)
const formulario = ref<SalvarParametroSistemaDto>({ instituicaoId: null, limiteUploadMb: 10, notificacaoInterna: true, notificacaoEmail: true, antecedenciaExpiracaoDias: 7, fusoHorario: 'America/Sao_Paulo' })
const fusos = [{ label: 'Brasília — São Paulo', value: 'America/Sao_Paulo' }, { label: 'Amazonas — Manaus', value: 'America/Manaus' }, { label: 'Acre — Rio Branco', value: 'America/Rio_Branco' }, { label: 'Fernando de Noronha', value: 'America/Noronha' }, { label: 'UTC', value: 'UTC' }]
const instituicoesDisponiveis = computed(() => instituicoes.value.filter((instituicao) => !parametros.value.some((parametro) => parametro.instituicaoId === instituicao.id)))

async function carregar(): Promise<void> { loading.value = true; erro.value = ''; try { parametros.value = await parametroSistemaService.listar(); if (props.usuario.adminGlobal) instituicoes.value = (await instituicaoService.listar('', 1, 100)).itens.filter((item) => item.ativo) } catch { erro.value = 'Não foi possível carregar os parâmetros.' } finally { loading.value = false } }
function abrirEdicao(item: ParametroSistemaDto): void { parametroAtual.value = item; formulario.value = { instituicaoId: item.instituicaoId, limiteUploadMb: item.limiteUploadMb, notificacaoInterna: item.notificacaoInterna, notificacaoEmail: item.notificacaoEmail, antecedenciaExpiracaoDias: item.antecedenciaExpiracaoDias, fusoHorario: item.fusoHorario }; dialogForm.value = true }
function abrirNovo(): void { parametroAtual.value = null; formulario.value = { instituicaoId: props.usuario.adminGlobal ? (instituicoesDisponiveis.value[0]?.id ?? null) : props.usuario.instituicaoId, limiteUploadMb: 10, notificacaoInterna: true, notificacaoEmail: true, antecedenciaExpiracaoDias: 7, fusoHorario: 'America/Sao_Paulo' }; dialogForm.value = true }
async function salvar(): Promise<void> {
  if (!formulario.value.notificacaoInterna && !formulario.value.notificacaoEmail) { erro.value = 'Mantenha ao menos um canal de notificação habilitado.'; return }
  if (!formulario.value.limiteUploadMb || formulario.value.limiteUploadMb < 1 || formulario.value.limiteUploadMb > 100) { erro.value = 'O limite de upload deve ficar entre 1 e 100 MB.'; return }
  saving.value = true; erro.value = ''; sucesso.value = ''
  try { if (parametroAtual.value) await parametroSistemaService.atualizar(parametroAtual.value.id, formulario.value); else await parametroSistemaService.criar(formulario.value); dialogForm.value = false; sucesso.value = 'Parâmetros salvos e registrados na auditoria.'; await carregar() } catch { erro.value = 'Não foi possível salvar os parâmetros. Confira o escopo e os valores.' } finally { saving.value = false }
}
function formatarData(valor: string): string { return new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(valor)) }
onMounted(carregar)
</script>

<template>
  <section class="pagina-crud">
    <div class="barra-voltar"><Button label="Voltar" icon="pi pi-arrow-left" text @click="emit('voltar')" /></div>
    <header class="cabecalho-crud"><div><p class="identificador-secao">CONFIGURAÇÕES</p><h1>Parâmetros do sistema</h1><p>Configure limites operacionais, notificações e fuso horário.</p></div><Button v-if="(props.usuario.adminGlobal && instituicoesDisponiveis.length) || (!props.usuario.adminGlobal && !parametros.length)" label="Nova configuração institucional" icon="pi pi-plus" @click="abrirNovo" /></header>
    <Message v-if="erro" severity="error" closable @close="erro = ''">{{ erro }}</Message><Message v-if="sucesso" severity="success" closable @close="sucesso = ''">{{ sucesso }}</Message>
    <Message severity="info" :closable="false">Configurações institucionais substituem o padrão global somente para a respectiva instituição.</Message>
    <DataTable :value="parametros" :loading="loading" data-key="id" striped-rows>
      <template #empty>Nenhuma configuração institucional cadastrada.</template>
      <Column header="Escopo"><template #body="{ data }"><div class="celula-principal"><strong>{{ data.escopo === 'GLOBAL' ? 'Padrão global' : data.instituicaoNome }}</strong><span>{{ data.escopo === 'GLOBAL' ? 'Todas as instituições sem configuração própria' : 'Configuração institucional' }}</span></div></template></Column>
      <Column header="Upload"><template #body="{ data }"><strong>{{ data.limiteUploadMb }} MB</strong></template></Column>
      <Column header="Notificações"><template #body="{ data }"><div class="tags-parametros"><Tag v-if="data.notificacaoInterna" value="Interna" severity="info" /><Tag v-if="data.notificacaoEmail" value="E-mail" severity="secondary" /></div></template></Column>
      <Column header="Aviso de expiração"><template #body="{ data }">{{ data.antecedenciaExpiracaoDias }} dia(s)</template></Column>
      <Column field="fusoHorario" header="Fuso horário" /><Column header="Atualizado"><template #body="{ data }">{{ formatarData(data.atualizadoEm) }}</template></Column>
      <Column header="Ações" body-class="acoes-tabela"><template #body="{ data }"><Button icon="pi pi-pencil" text rounded aria-label="Editar parâmetros" @click="abrirEdicao(data)" /></template></Column>
    </DataTable>
    <Dialog v-model:visible="dialogForm" modal :header="parametroAtual ? `Editar ${parametroAtual.escopo === 'GLOBAL' ? 'padrão global' : 'configuração institucional'}` : 'Nova configuração institucional'" :style="{ width: 'min(40rem, 95vw)' }">
      <form class="formulario-dialog" @submit.prevent="salvar">
        <label v-if="!parametroAtual && props.usuario.adminGlobal">Instituição <Select v-model="formulario.instituicaoId" :options="instituicoesDisponiveis" option-label="nome" option-value="id" filter placeholder="Selecione" /></label>
        <div class="linha-formulario linha-formulario-igual"><label>Limite de upload <InputNumber v-model="formulario.limiteUploadMb" suffix=" MB" :min="1" :max="100" /></label><label>Antecedência da expiração <InputNumber v-model="formulario.antecedenciaExpiracaoDias" suffix=" dias" :min="0" :max="365" /></label></div>
        <label>Fuso horário <Select v-model="formulario.fusoHorario" :options="fusos" option-label="label" option-value="value" /></label>
        <fieldset class="grupo-canais"><legend>Canais de notificação</legend><label class="campo-switch"><InputSwitch v-model="formulario.notificacaoInterna" /><span>Notificações internas</span></label><label class="campo-switch"><InputSwitch v-model="formulario.notificacaoEmail" /><span>Notificações por e-mail</span></label></fieldset>
      </form>
      <template #footer><Button label="Cancelar" severity="secondary" text @click="dialogForm = false" /><Button label="Salvar parâmetros" icon="pi pi-check" :loading="saving" :disabled="!parametroAtual && !formulario.instituicaoId" @click="salvar" /></template>
    </Dialog>
  </section>
</template>
