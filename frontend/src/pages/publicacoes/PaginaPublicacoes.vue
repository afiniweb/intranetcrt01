<script setup lang="ts">
import { onMounted, ref } from 'vue'
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import Drawer from 'primevue/drawer'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import ProgressBar from 'primevue/progressbar'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'
import { useToast } from 'primevue/usetoast'
import type { TipoConteudoDashboardDto } from '../../dto/dashboard/DashboardDto'
import type { PublicacaoDto, SalvarPublicacaoDto } from '../../dto/publicacao/PublicacaoDto'
import type { UsuarioDto } from '../../dto/usuario/UsuarioDto'
import { dashboardService } from '../../services/dashboardService'
import { publicacaoService } from '../../services/publicacaoService'

const props = defineProps<{ usuario: UsuarioDto; tipoInicialId?: number | null }>()
const emit = defineEmits<{ voltar: [] }>()
const toast = useToast()
const itens = ref<PublicacaoDto[]>([]), tipos = ref<TipoConteudoDashboardDto[]>([])
const busca = ref(''), carregando = ref(false), salvando = ref(false), erro = ref(''), dialog = ref(false)
const arquivoPdf = ref<File | null>(null)
const progressoUpload = ref<number | null>(null), detalhesVisiveis = ref(false), publicacaoAtual = ref<PublicacaoDto | null>(null)
const formulario = ref<SalvarPublicacaoDto>({ tipoConteudoId: 0, titulo: '', corpo: '', anexoUrl: null })
async function carregar(): Promise<void> { carregando.value = true; erro.value = ''; try { const resposta = await publicacaoService.listar(busca.value); itens.value = resposta.itens } catch { erro.value = 'Não foi possível carregar as publicações.' } finally { carregando.value = false } }
async function carregarTipos(): Promise<void> { if (props.usuario.perfil !== 'PUBLICADOR') return; try { tipos.value = (await dashboardService.listarTipos()).filter(tipo => tipo.responsavelPeloTipo) } catch { erro.value = 'Não foi possível carregar os tipos sob sua responsabilidade.' } }
function nova(tipoId?: number | null): void { const tipoPermitido = tipos.value.find(tipo => tipo.id === tipoId); formulario.value = { tipoConteudoId: tipoPermitido?.id ?? tipos.value[0]?.id ?? 0, titulo: '', corpo: '', anexoUrl: null }; arquivoPdf.value = null; dialog.value = true; erro.value = '' }
function alerta(severity: 'success' | 'error' | 'warn', summary: string, detail: string): void { toast.add({ severity, summary, detail, life: 5000 }) }
function selecionarArquivo(evento: Event): void { const input = evento.target as HTMLInputElement; const arquivo = input.files?.[0] ?? null; if (!arquivo) { arquivoPdf.value = null; return } const nomePdf = arquivo.name.toLowerCase().endsWith('.pdf'), mimePdf = arquivo.type === 'application/pdf'; if (!nomePdf || !mimePdf) { arquivoPdf.value = null; input.value = ''; alerta('error', 'Arquivo inválido', 'Selecione somente um arquivo PDF válido.'); return } if (arquivo.size > 10 * 1024 * 1024) { arquivoPdf.value = null; input.value = ''; alerta('error', 'Arquivo muito grande', 'O arquivo PDF deve possuir no máximo 10 MB.'); return } arquivoPdf.value = arquivo; erro.value = '' }
async function salvar(): Promise<void> { if (!formulario.value.tipoConteudoId || !formulario.value.titulo.trim() || !formulario.value.corpo.trim()) { alerta('warn', 'Campos obrigatórios', 'Informe tipo, título e conteúdo.'); return } if (!formulario.value.anexoUrl?.trim() && !arquivoPdf.value) { alerta('warn', 'Conteúdo complementar', 'Informe um link externo, selecione um arquivo PDF ou utilize as duas opções.'); return } salvando.value = true; progressoUpload.value = 0; erro.value = ''; try { await publicacaoService.criar(formulario.value, arquivoPdf.value, percentual => progressoUpload.value = percentual); dialog.value = false; alerta('success', 'Publicação criada', 'Conteúdo publicado e disponibilizado imediatamente.'); await carregar() } catch { alerta('error', 'Não foi possível publicar', 'Confira os dados, o link externo e o arquivo PDF.') } finally { salvando.value = false; progressoUpload.value = null } }
async function alterar(item: PublicacaoDto): Promise<void> { salvando.value = true; erro.value = ''; try { if (item.status === 'PUBLICADA') { await publicacaoService.arquivar(item.id); alerta('success', 'Publicação desabilitada', 'O conteúdo não está mais disponível no dashboard.') } else { await publicacaoService.reativar(item.id); alerta('success', 'Publicação reativada', 'O conteúdo voltou a ficar disponível no dashboard.') } await carregar() } catch { alerta('error', 'Operação não realizada', 'Não foi possível alterar a disponibilidade da publicação.') } finally { salvando.value = false } }
function data(valor: string | null): string { return valor ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(valor)) : '—' }
function visualizar(item: PublicacaoDto): void { publicacaoAtual.value = item; detalhesVisiveis.value = true }
onMounted(async () => { await Promise.all([carregar(), carregarTipos()]); if (props.tipoInicialId && props.usuario.perfil === 'PUBLICADOR') nova(props.tipoInicialId) })
</script>

<template>
  <section class="pagina-crud">
    <div class="barra-voltar"><Button label="Voltar" icon="pi pi-arrow-left" text severity="secondary" @click="emit('voltar')" /></div>
    <div class="cabecalho-crud"><div><p class="identificador-secao">GESTÃO EDITORIAL</p><h1>Publicações</h1><p>Conteúdos novos ficam disponíveis imediatamente e podem ser desabilitados sem exclusão definitiva.</p></div><Button v-if="usuario.perfil === 'PUBLICADOR'" label="Nova publicação" icon="pi pi-plus" :disabled="!tipos.length" @click="nova()" /></div>
    <Message v-if="erro" severity="error" closable @close="erro = ''">{{ erro }}</Message>
    <div class="ferramentas-lista"><InputText v-model="busca" placeholder="Pesquisar por título" @keyup.enter="carregar" /><Button label="Pesquisar" icon="pi pi-search" outlined @click="carregar" /></div>
    <DataTable :value="itens" :loading="carregando" data-key="id" striped-rows paginator :rows="10" :rows-per-page-options="[10, 25, 50]" empty-message="Nenhuma publicação encontrada.">
      <Column header="Publicação"><template #body="slot"><button class="titulo-publicacao-link" type="button" @click="visualizar(slot.data)"><strong>{{ slot.data.titulo }}</strong><span>{{ slot.data.tipoConteudo }} · {{ slot.data.autorNome }}</span></button></template></Column>
      <Column header="Instituição"><template #body="slot"><div class="celula-principal"><span>{{ slot.data.instituicaoNome }}</span><span>{{ slot.data.escritorioNome }}</span></div></template></Column>
      <Column header="Publicada em"><template #body="slot">{{ data(slot.data.publicadaEm) }}</template></Column>
      <Column header="Recursos"><template #body="slot"><div class="indicadores-recursos"><span v-if="slot.data.anexoUrl" title="Link externo"><i class="pi pi-external-link" /></span><span v-if="slot.data.arquivoUrl" class="recurso-pdf" title="Arquivo PDF"><i class="pi pi-file-pdf" /></span><small v-if="!slot.data.anexoUrl && !slot.data.arquivoUrl">Nenhum</small></div></template></Column>
      <Column header="Situação"><template #body="slot"><Tag :value="slot.data.status === 'PUBLICADA' ? 'Ativa' : 'Desabilitada'" :severity="slot.data.status === 'PUBLICADA' ? 'success' : 'secondary'" /></template></Column>
      <Column header="Ações" body-class="acoes-tabela"><template #body="slot"><Button icon="pi pi-search" text rounded severity="secondary" aria-label="Visualizar detalhes" @click="visualizar(slot.data)" /><Button :label="slot.data.status === 'PUBLICADA' ? 'Desabilitar' : 'Reativar'" :icon="slot.data.status === 'PUBLICADA' ? 'pi pi-eye-slash' : 'pi pi-eye'" text :severity="slot.data.status === 'PUBLICADA' ? 'danger' : 'success'" :loading="salvando" @click="alterar(slot.data)" /></template></Column>
    </DataTable>
    <Dialog v-model:visible="dialog" modal header="Nova publicação" :style="{ width: 'min(42rem, 95vw)' }">
      <div class="formulario-dialog"><Message severity="info" :closable="false">Ao confirmar, o conteúdo ficará disponível imediatamente para a instituição.</Message><label>Tipo de conteúdo <Select v-model="formulario.tipoConteudoId" :options="tipos" option-label="nome" option-value="id" placeholder="Selecione" /></label><label>Título <InputText v-model="formulario.titulo" maxlength="150" /></label><label>Conteúdo <Textarea v-model="formulario.corpo" rows="8" maxlength="10000" auto-resize /></label><label>Link para conteúdo externo (opcional) <InputText v-model="formulario.anexoUrl" type="url" maxlength="500" placeholder="https://" /></label><label>Arquivo PDF (opcional) <input class="campo-arquivo" type="file" accept="application/pdf,.pdf" @change="selecionarArquivo" /><small>Somente PDF, com tamanho máximo de 10 MB.</small></label><div v-if="progressoUpload !== null" class="progresso-upload"><div><span>Enviando publicação</span><strong>{{ progressoUpload }}%</strong></div><ProgressBar :value="progressoUpload" /></div></div>
      <template #footer><Button label="Cancelar" text severity="secondary" @click="dialog = false" /><Button label="Publicar agora" icon="pi pi-send" :loading="salvando" @click="salvar" /></template>
    </Dialog>
    <Drawer v-model:visible="detalhesVisiveis" position="right" header="Detalhes da publicação" class="painel-detalhes-publicacao"><article v-if="publicacaoAtual" class="detalhes-publicacao"><div><Tag :value="publicacaoAtual.status === 'PUBLICADA' ? 'Ativa' : 'Desabilitada'" :severity="publicacaoAtual.status === 'PUBLICADA' ? 'success' : 'secondary'" /><span>{{ data(publicacaoAtual.publicadaEm) }}</span></div><div><p class="identificador-secao">{{ publicacaoAtual.tipoConteudo }}</p><h2>{{ publicacaoAtual.titulo }}</h2></div><p class="corpo-publicacao">{{ publicacaoAtual.corpo }}</p><dl class="metadados-publicacao"><div><dt>Autor</dt><dd>{{ publicacaoAtual.autorNome }}</dd></div><div><dt>Instituição</dt><dd>{{ publicacaoAtual.instituicaoNome }}</dd></div><div><dt>Escritório</dt><dd>{{ publicacaoAtual.escritorioNome }}</dd></div></dl><div class="recursos-publicacao"><a v-if="publicacaoAtual.anexoUrl" :href="publicacaoAtual.anexoUrl" target="_blank" rel="noopener"><i class="pi pi-external-link" /> Visualizar conteúdo externo</a><a v-if="publicacaoAtual.arquivoUrl && publicacaoAtual.status === 'PUBLICADA'" :href="publicacaoAtual.arquivoUrl" target="_blank" rel="noopener"><i class="pi pi-file-pdf" /> Visualizar PDF</a></div></article></Drawer>
  </section>
</template>
