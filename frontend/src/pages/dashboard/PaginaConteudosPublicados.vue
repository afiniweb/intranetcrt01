<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import type { PublicacaoDashboardDto, TipoConteudoDashboardDto } from '../../dto/dashboard/DashboardDto'
import { dashboardService } from '../../services/dashboardService'

const props = defineProps<{ tipo: TipoConteudoDashboardDto }>()
const emit = defineEmits<{ voltar: [] }>()
const publicacoes = ref<PublicacaoDashboardDto[]>([])
const busca = ref(''), escritorioFiltro = ref<string | null>(null), loading = ref(false), erro = ref('')
const escritorios = computed(() => [...new Set(publicacoes.value.map(item => item.escritorioNome))].sort((a, b) => a.localeCompare(b, 'pt-BR')))
const publicacoesFiltradas = computed(() => { const termo = busca.value.trim().toLocaleLowerCase('pt-BR'); return publicacoes.value.filter(item => { const correspondeEscritorio = !escritorioFiltro.value || item.escritorioNome === escritorioFiltro.value; const texto = [item.titulo, item.corpo, item.autorNome, item.escritorioNome].join(' ').toLocaleLowerCase('pt-BR'); return correspondeEscritorio && (!termo || texto.includes(termo)) }) })
async function carregar(): Promise<void> { loading.value = true; erro.value = ''; try { publicacoes.value = (await dashboardService.listarPublicacoes(props.tipo.id, 1, 50)).itens } catch { erro.value = 'Não foi possível carregar as publicações.'; publicacoes.value = [] } finally { loading.value = false } }
function limparFiltros(): void { busca.value = ''; escritorioFiltro.value = null }
function formatarData(valor: string | null): string { return valor ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'medium' }).format(new Date(valor)) : '—' }
onMounted(carregar)
</script>

<template>
  <section class="pagina-crud pagina-conteudos-publicados">
    <div class="barra-voltar"><Button label="Voltar para conteúdos" icon="pi pi-arrow-left" text severity="secondary" @click="emit('voltar')" /></div>
    <header class="cabecalho-crud"><div><p class="identificador-secao">PUBLICAÇÕES</p><h1>{{ props.tipo.nome }}</h1><p>{{ props.tipo.descricao || 'Consulte os conteúdos disponíveis nesta categoria.' }}</p></div><span class="total-resultados">{{ publicacoesFiltradas.length }} resultado{{ publicacoesFiltradas.length === 1 ? '' : 's' }}</span></header>
    <Message v-if="erro" severity="error" closable @close="erro = ''">{{ erro }}</Message>
    <div class="ferramentas-lista ferramentas-publicacoes-home"><InputText v-model="busca" placeholder="Buscar por título, conteúdo, autor ou escritório" aria-label="Buscar publicações" /><Select v-model="escritorioFiltro" :options="escritorios" show-clear placeholder="Todos os escritórios" aria-label="Filtrar por escritório" /><Button label="Limpar filtros" icon="pi pi-filter-slash" severity="secondary" text :disabled="!busca && !escritorioFiltro" @click="limparFiltros" /></div>
    <DataTable :value="publicacoesFiltradas" :loading="loading" data-key="id" striped-rows paginator :rows="10" :rows-per-page-options="[10, 25, 50]" empty-message="Nenhuma publicação encontrada com os filtros informados.">
      <Column field="titulo" header="Publicação" sortable><template #body="slot"><div class="celula-publicacao-home"><strong>{{ slot.data.titulo }}</strong><span>{{ slot.data.corpo }}</span></div></template></Column>
      <Column field="autorNome" header="Autor" sortable />
      <Column field="escritorioNome" header="Escritório" sortable />
      <Column field="publicadaEm" header="Publicada em" sortable><template #body="slot">{{ formatarData(slot.data.publicadaEm) }}</template></Column>
      <Column header="Recursos" body-class="acoes-tabela"><template #body="slot"><div class="links-publicacao links-publicacao-tabela"><a v-if="slot.data.anexoUrl" :href="slot.data.anexoUrl" target="_blank" rel="noopener"><i class="pi pi-external-link" /> Link</a><a v-if="slot.data.arquivoUrl" :href="slot.data.arquivoUrl" target="_blank" rel="noopener"><i class="pi pi-file-pdf" /> PDF</a><span v-if="!slot.data.anexoUrl && !slot.data.arquivoUrl">—</span></div></template></Column>
    </DataTable>
  </section>
</template>
