<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from "vue"
import ProgressSpinner from "primevue/progressspinner"
import Toast from "primevue/toast"
import DialogMensagens from "./components/DialogMensagens.vue"
import RodapeSistema from "./components/RodapeSistema.vue"
import type { UsuarioDto } from "./dto/usuario/UsuarioDto"
import PaginaAdministracao from "./pages/admin/PaginaAdministracao.vue"
import PaginaLogin from "./pages/autenticacao/PaginaLogin.vue"
import PaginaDashboard from "./pages/dashboard/PaginaDashboard.vue"
import PaginaPublicacoes from "./pages/publicacoes/PaginaPublicacoes.vue"
import { autenticacaoService } from "./services/autenticacaoService"
const usuario = ref<UsuarioDto | null>(null), verificando = ref(true)
const mensagensVerificadas = ref(false)
watch(() => usuario.value?.id, () => { mensagensVerificadas.value = false })
function bloquearMensagens(): void { mensagensVerificadas.value = false }
window.addEventListener('mensagem-pendente', bloquearMensagens)
onUnmounted(() => window.removeEventListener('mensagem-pendente', bloquearMensagens))
const tela = ref<"dashboard" | "admin" | "publicacoes">("dashboard")
const tipoInicialPublicacaoId = ref<number | null>(null)
async function sair(): Promise<void> { try { await autenticacaoService.logout() } finally { usuario.value = null; tela.value = "dashboard" } }
function abrirPublicacoes(tipoId: number | null = null): void { tipoInicialPublicacaoId.value = tipoId; tela.value = "publicacoes" }
onMounted(async () => { try { usuario.value = await autenticacaoService.me() } catch { usuario.value = null } finally { verificando.value = false } })
</script>
<template><div class="aplicacao"><Toast position="top-right" /><div v-if="verificando" class="carregando-aplicacao"><ProgressSpinner /></div><PaginaLogin v-else-if="!usuario" @autenticado="usuario = $event" /><DialogMensagens v-else-if="!mensagensVerificadas" @concluido="mensagensVerificadas = true" @sair="sair" /><PaginaAdministracao v-else-if="usuario.perfil === 'ADMIN' && tela === 'admin'" :usuario="usuario" @logout="sair" @dashboard="tela = 'dashboard'" /><div v-else-if="tela === 'publicacoes' && (usuario.perfil === 'ADMIN' || usuario.perfil === 'PUBLICADOR')" class="layout-administracao"><main class="conteudo-administracao"><PaginaPublicacoes :usuario="usuario" :tipo-inicial-id="tipoInicialPublicacaoId" @voltar="tela = usuario.perfil === 'ADMIN' ? 'admin' : 'dashboard'" /></main></div><PaginaDashboard v-else :usuario="usuario" @logout="sair" @administrar="tela = 'admin'" @publicacoes="abrirPublicacoes()" @nova-publicacao="abrirPublicacoes" /><RodapeSistema /></div></template>
