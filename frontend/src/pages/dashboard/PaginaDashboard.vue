<script setup lang="ts">
import { onMounted, ref } from 'vue'
import Avatar from 'primevue/avatar'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import type { TipoConteudoDashboardDto } from '../../dto/dashboard/DashboardDto'
import type { UsuarioDto } from '../../dto/usuario/UsuarioDto'
import { dashboardService } from '../../services/dashboardService'
import PaginaConteudosPublicados from './PaginaConteudosPublicados.vue'

defineProps<{ usuario: UsuarioDto }>()
const emit = defineEmits<{ logout: []; administrar: []; publicacoes: []; novaPublicacao: [tipoId: number] }>()
const cores = ['#2563eb','#0f766e','#7c3aed','#c2410c','#047857','#be123c','#0369a1','#4d7c0f']
const tipos = ref<TipoConteudoDashboardDto[]>([])
const tipoAtual = ref<TipoConteudoDashboardDto | null>(null)
const loading = ref(false), erro = ref('')
async function carregar(): Promise<void> { loading.value = true; erro.value = ''; try { tipos.value = await dashboardService.listarTipos() } catch { erro.value = 'Não foi possível carregar os conteúdos da Intranet.' } finally { loading.value = false } }
function abrirTipo(tipo: TipoConteudoDashboardDto): void { tipoAtual.value = tipo }
onMounted(carregar)
</script>

<template>
  <div class="layout-administracao">
    <header class="cabecalho-administracao"><div class="marca-sistema"><img class="logomarca-header" src="/images/logo-crt-01-alfa.png" alt="Logomarca CRT-01" /><div><strong>Intranet CRT-01</strong><span>Conteúdos institucionais</span></div></div><div class="acoes-cabecalho"><Button v-if="usuario.perfil === 'PUBLICADOR'" label="Publicações" icon="pi pi-file-edit" text @click="emit('publicacoes')" /><Button v-if="usuario.perfil === 'ADMIN'" label="Administração" icon="pi pi-cog" text @click="emit('administrar')" /><div class="usuario-atual"><Avatar :label="usuario.nome.slice(0,2).toUpperCase()" shape="circle" /><div><strong>{{ usuario.nome }}</strong><span>{{ usuario.perfil === 'ADMIN' ? 'Admin' : 'Publicador' }}</span></div><Button icon="pi pi-sign-out" text rounded aria-label="Sair" @click="emit('logout')" /></div></div></header>
    <main class="conteudo-administracao">
      <PaginaConteudosPublicados v-if="tipoAtual" :tipo="tipoAtual" @voltar="tipoAtual = null" />
      <template v-else><section class="apresentacao-pagina"><p class="identificador-secao">CONTEÚDOS</p><h1>Informações da sua instituição</h1><p>Acesse comunicados, documentos e orientações publicados para todos os usuários.</p></section><Message v-if="erro" severity="error" closable @close="erro = ''">{{ erro }}</Message><div v-if="loading" class="loading-dashboard"><ProgressSpinner /></div><Message v-else-if="!tipos.length" severity="info" :closable="false">Nenhum tipo de conteúdo ativo disponível.</Message><section v-else class="grade-tipos-dashboard" aria-label="Tipos de conteúdo"><Card v-for="(tipo, indice) in tipos" :key="tipo.id" class="card-tipo-dashboard" :style="{ '--cor-card': cores[indice % cores.length] }"><template #header><div class="icone-opcao icone-tipo-dashboard"><i class="pi pi-folder-open" aria-hidden="true" /></div></template><template #title>{{ tipo.nome }}</template><template #content><p class="descricao-opcao descricao-tipo-dashboard">{{ tipo.descricao || 'Consulte os conteúdos publicados nesta categoria.' }}</p><div class="resumo-tipo-dashboard"><strong>{{ tipo.totalPublicadas }}</strong><span>publicação{{ tipo.totalPublicadas === 1 ? '' : 'ões' }}</span></div></template><template #footer><div class="acoes-card-dashboard"><Button class="botao-ver-publicacoes" label="Ver publicações" icon="pi pi-arrow-right" icon-pos="right" size="small" :disabled="!tipo.totalPublicadas" @click="abrirTipo(tipo)" /><Button v-if="tipo.responsavelPeloTipo" class="botao-nova-publicacao" label="Nova publicação" icon="pi pi-plus" size="small" outlined @click="emit('novaPublicacao', tipo.id)" /></div></template></Card></section></template>
    </main>
  </div>
</template>
