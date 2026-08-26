<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import Avatar from 'primevue/avatar'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Tag from 'primevue/tag'
import CardOpcaoAdministrativa from '../../components/admin/CardOpcaoAdministrativa.vue'
import type { HealthResponseDto } from '../../dto/health/HealthResponseDto'
import type { UsuarioDto } from '../../dto/usuario/UsuarioDto'
import { healthService } from '../../services/healthService'
import PaginaInstituicoes from './instituicoes/PaginaInstituicoes.vue'
import PaginaEscritorios from './escritorios/PaginaEscritorios.vue'
import PaginaUsuarios from './usuarios/PaginaUsuarios.vue'
import PaginaTiposConteudo from './tiposConteudo/PaginaTiposConteudo.vue'
import PaginaPublicacoes from '../publicacoes/PaginaPublicacoes.vue'
import PaginaParametrosSistema from './parametros/PaginaParametrosSistema.vue'

const props = defineProps<{ usuario: UsuarioDto }>()
const emit = defineEmits<{ logout: []; dashboard: [] }>()

interface OpcaoAdministrativa { titulo: string; descricao: string; icone: string; cor: string; modulo: string }

const opcoesCadastro: OpcaoAdministrativa[] = [
  { modulo: 'instituicoes', titulo: 'Instituições', descricao: 'Cadastre e mantenha as instituições que utilizarão a plataforma.', icone: 'pi pi-building', cor: '#2563eb' },
  { modulo: 'escritorios', titulo: 'Escritórios regionais', descricao: 'Organize os escritórios por instituição, estado e localização.', icone: 'pi pi-map-marker', cor: '#0f766e' },
  { modulo: 'usuarios', titulo: 'Usuários', descricao: 'Gerencie usuários, perfis de acesso, situação e remanejamentos.', icone: 'pi pi-users', cor: '#7c3aed' },
  { modulo: 'tipos-conteudo', titulo: 'Tipos de conteúdo', descricao: 'Defina os tipos de publicação e seus Publicadores responsáveis.', icone: 'pi pi-objects-column', cor: '#c2410c' },
]
const opcoesGestao: OpcaoAdministrativa[] = [
  { modulo: 'publicacoes', titulo: 'Publicações', descricao: 'Consulte conteúdos ativos e desabilite ou reative o acesso quando necessário.', icone: 'pi pi-file-edit', cor: '#047857' },
  { modulo: 'parametros', titulo: 'Parâmetros do sistema', descricao: 'Configure limites de upload, notificações e preferências institucionais.', icone: 'pi pi-cog', cor: '#475569' },
]
const health = ref<HealthResponseDto | null>(null)
const moduloAtivo = ref('')
const moduloSelecionado = ref('')
const apiDisponivel = computed(() => health.value?.status === 'ok')
async function checkHealth(): Promise<void> { try { health.value = await healthService.check() } catch { health.value = null } }
function selecionarModulo(opcao: OpcaoAdministrativa): void {
  if (['instituicoes', 'escritorios', 'usuarios', 'tipos-conteudo', 'publicacoes', 'parametros'].includes(opcao.modulo)) moduloAtivo.value = opcao.modulo
  else moduloSelecionado.value = opcao.titulo
}
onMounted(checkHealth)
</script>

<template>
  <div class="layout-administracao">
    <header class="cabecalho-administracao">
      <div class="marca-sistema"><img class="logomarca-header" src="/images/logo-crt-01-alfa.png" alt="Logomarca CRT-01" /><div><strong>Intranet CRT-01</strong><span>Administração</span></div></div>
      <div class="acoes-cabecalho">
        <Button label="Conteúdos" icon="pi pi-home" text @click="emit('dashboard')" />
        <Tag :value="apiDisponivel ? 'Sistema disponível' : 'Verificando sistema'" :severity="apiDisponivel ? 'success' : 'secondary'" :icon="apiDisponivel ? 'pi pi-check-circle' : 'pi pi-spin pi-spinner'" />
        <div class="usuario-atual"><Avatar :label="props.usuario.nome.slice(0, 2).toUpperCase()" shape="circle" /><div><strong>{{ props.usuario.nome }}</strong><span>{{ props.usuario.adminGlobal ? 'Admin global' : props.usuario.perfil === 'ADMIN' ? 'Admin' : 'Publicador' }}</span></div><Button icon="pi pi-sign-out" text rounded aria-label="Sair" @click="emit('logout')" /></div>
      </div>
    </header>
    <main class="conteudo-administracao">
      <PaginaInstituicoes v-if="moduloAtivo === 'instituicoes'" @voltar="moduloAtivo = ''" />
      <PaginaEscritorios v-else-if="moduloAtivo === 'escritorios'" @voltar="moduloAtivo = ''" />
      <PaginaUsuarios v-else-if="moduloAtivo === 'usuarios'" @voltar="moduloAtivo = ''" />
      <PaginaTiposConteudo v-else-if="moduloAtivo === 'tipos-conteudo'" @voltar="moduloAtivo = ''" />
      <PaginaPublicacoes v-else-if="moduloAtivo === 'publicacoes'" :usuario="props.usuario" @voltar="moduloAtivo = ''" />
      <PaginaParametrosSistema v-else-if="moduloAtivo === 'parametros'" :usuario="props.usuario" @voltar="moduloAtivo = ''" />
      <template v-else>
        <section class="apresentacao-pagina" aria-labelledby="titulo-administracao"><p class="identificador-secao">PAINEL ADMINISTRATIVO</p><h1 id="titulo-administracao">Área de administração</h1><p>Gerencie os cadastros e configurações da Intranet CRT-01.</p></section>
        <Message v-if="moduloSelecionado" severity="info" closable @close="moduloSelecionado = ''">O módulo <strong>{{ moduloSelecionado }}</strong> será disponibilizado após a conclusão do cadastro de instituições.</Message>
        <section class="grupo-opcoes" aria-labelledby="titulo-cadastros">
          <div class="cabecalho-grupo"><div><h2 id="titulo-cadastros">Cadastros</h2><p>Dados essenciais para a organização da plataforma.</p></div><span>{{ opcoesCadastro.length }} opções</span></div>
          <div class="grade-opcoes"><CardOpcaoAdministrativa v-for="opcao in opcoesCadastro" :key="opcao.modulo" v-bind="opcao" @selecionar="selecionarModulo(opcao)" /></div>
        </section>
        <section class="grupo-opcoes" aria-labelledby="titulo-gestao">
          <div class="cabecalho-grupo"><div><h2 id="titulo-gestao">Gestão e configurações</h2><p>Acompanhe o fluxo editorial e os parâmetros institucionais.</p></div><span>{{ opcoesGestao.length }} opções</span></div>
          <div class="grade-opcoes grade-opcoes-secundaria"><CardOpcaoAdministrativa v-for="opcao in opcoesGestao" :key="opcao.modulo" v-bind="opcao" @selecionar="selecionarModulo(opcao)" /></div>
        </section>
      </template>
    </main>
  </div>
</template>
