<script setup lang="ts">
import { onMounted, ref } from "vue"
import ProgressSpinner from "primevue/progressspinner"
import Toast from "primevue/toast"
import type { UsuarioDto } from "./dto/usuario/UsuarioDto"
import PaginaAdministracao from "./pages/admin/PaginaAdministracao.vue"
import PaginaLogin from "./pages/autenticacao/PaginaLogin.vue"
import PaginaDashboard from "./pages/dashboard/PaginaDashboard.vue"
import PaginaPublicacoes from "./pages/publicacoes/PaginaPublicacoes.vue"
import { autenticacaoService } from "./services/autenticacaoService"
const usuario = ref<UsuarioDto | null>(null), verificando = ref(true)
const tela = ref<"dashboard" | "admin" | "publicacoes">("dashboard")
const tipoInicialPublicacaoId = ref<number | null>(null)
async function sair(): Promise<void> { try { await autenticacaoService.logout() } finally { usuario.value = null; tela.value = "dashboard" } }
function abrirPublicacoes(tipoId: number | null = null): void { tipoInicialPublicacaoId.value = tipoId; tela.value = "publicacoes" }
onMounted(async () => { try { usuario.value = await autenticacaoService.me() } catch { usuario.value = null } finally { verificando.value = false } })
</script>
<template><Toast position="top-right" /><div v-if="verificando" class="carregando-aplicacao"><ProgressSpinner /></div><PaginaLogin v-else-if="!usuario" @autenticado="usuario = $event" /><PaginaAdministracao v-else-if="usuario.perfil === 'ADMIN' && tela === 'admin'" :usuario="usuario" @logout="sair" @dashboard="tela = 'dashboard'" /><div v-else-if="tela === 'publicacoes'" class="layout-administracao"><main class="conteudo-administracao"><PaginaPublicacoes :usuario="usuario" :tipo-inicial-id="tipoInicialPublicacaoId" @voltar="tela = usuario.perfil === 'ADMIN' ? 'admin' : 'dashboard'" /></main></div><PaginaDashboard v-else :usuario="usuario" @logout="sair" @administrar="tela = 'admin'" @publicacoes="abrirPublicacoes()" @nova-publicacao="abrirPublicacoes" /></template>
