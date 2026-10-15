<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import Message from 'primevue/message'
import ProgressSpinner from 'primevue/progressspinner'
import { mensagemService, type Mensagem } from '../services/mensagemService'

const emit = defineEmits<{ concluido: []; sair: [] }>()
const mensagem = ref<Mensagem | null>(null), restantes = ref(30), ciente = ref(false), ocupado = ref(false), erro = ref('')
let fim = 0
let ativa = true
const intervalo = setInterval(() => { restantes.value = Math.max(0, Math.ceil((fim - Date.now()) / 1000)) }, 200)
async function carregar(): Promise<void> {
  ocupado.value = true; erro.value = ''; ciente.value = false; mensagem.value = null
  try {
    const proxima = await mensagemService.pendente()
    if (!ativa) return
    if (!proxima) { emit('concluido'); return }
    const resposta = await mensagemService.apresentar(proxima.id)
    if (!ativa) return
    mensagem.value = resposta.mensagem
    fim = Date.now() + resposta.restantes * 1000
    restantes.value = resposta.restantes
  } catch { erro.value = 'Não foi possível consultar as mensagens. Tente novamente para continuar.' }
  finally { ocupado.value = false }
}
async function responder(aceitar: boolean): Promise<void> {
  if (!mensagem.value || restantes.value > 0 || ocupado.value || (aceitar && !ciente.value)) return
  ocupado.value = true; erro.value = ''
  try { await mensagemService.responder(mensagem.value, aceitar); await carregar() }
  catch { erro.value = 'Não foi possível registrar a resposta. A mensagem pode ter mudado ou expirado. Atualize a consulta.' }
  finally { ocupado.value = false }
}
onMounted(carregar)
onUnmounted(() => { ativa = false; clearInterval(intervalo) })
</script>
<template>
  <Dialog class="dialog-mensagem" :visible="true" modal :closable="false" :close-on-escape="false" :dismissable-mask="false" :draggable="false" :header="mensagem?.titulo ?? 'Mensagens institucionais'" :style="{ width: '46rem', maxWidth: '95vw' }">
    <template #header>
      <div class="cabecalho-mensagem">
        <span class="icone-comunicado"><i class="pi pi-megaphone" aria-hidden="true" /></span>
        <div class="titulo-comunicado">
          <h2>{{ mensagem?.titulo ?? 'Mensagens institucionais' }}</h2>
        </div>
      </div>
    </template>
    <Message v-if="erro" severity="error" :closable="false">{{ erro }}</Message>
    <div v-if="ocupado && !mensagem" class="carregando-mensagem"><ProgressSpinner /><p>Consultando suas mensagens…</p></div>
    <template v-if="mensagem">
      <div class="metadados-mensagem">
        <span class="selo-ciencia" :class="{ opcional: !mensagem.obrigatoria }"><i :class="mensagem.obrigatoria ? 'pi pi-shield' : 'pi pi-info-circle'" aria-hidden="true" />{{ mensagem.obrigatoria ? 'Ciência obrigatória' : 'Ciência opcional' }}</span>
        <span class="orientacao-leitura">Leia com atenção</span>
      </div>
      <div class="texto-mensagem">{{ mensagem.texto }}</div>
      <div class="painel-leitura" :class="{ liberado: restantes === 0 }">
        <div class="status-leitura">
          <span class="icone-leitura"><i :class="restantes > 0 ? 'pi pi-clock' : 'pi pi-check-circle'" aria-hidden="true" /></span>
          <div><strong>{{ restantes > 0 ? 'Tempo de leitura' : 'Pronto para continuar' }}</strong><p v-if="restantes > 0" role="timer" aria-live="polite">Aguarde {{ restantes }} segundos para continuar.</p></div>
          <span class="contador-leitura" aria-hidden="true">{{ restantes > 0 ? `${restantes}s` : '✓' }}</span>
        </div>
        <div class="trilha-leitura" aria-hidden="true"><div :style="{ width: `${Math.min(100, Math.max(0, (30 - restantes) / 30 * 100))}%` }" /></div>
      </div>
      <label class="ciencia-mensagem" :class="{ bloqueada: restantes > 0 || ocupado, confirmada: ciente }">
        <Checkbox v-model="ciente" binary :disabled="restantes > 0 || ocupado" input-id="ciencia-mensagem" />
        <span><strong>Li e estou ciente</strong><small>Confirmo a leitura desta mensagem.</small></span>
      </label>
    </template>
    <template #footer>
      <div class="rodape-mensagem">
        <Button label="Sair do sistema" icon="pi pi-sign-out" text severity="secondary" @click="emit('sair')" />
        <div class="acoes-resposta">
          <Button v-if="erro" label="Atualizar consulta" icon="pi pi-refresh" :disabled="ocupado" @click="carregar" />
          <Button v-if="mensagem && !mensagem.obrigatoria" label="Agora não" severity="secondary" outlined :disabled="restantes > 0 || ocupado" @click="responder(false)" />
          <Button v-if="mensagem" label="Continuar" icon="pi pi-arrow-right" icon-pos="right" :disabled="restantes > 0 || !ciente || ocupado" :loading="ocupado" @click="responder(true)" />
        </div>
      </div>
    </template>
  </Dialog>
</template>
<style scoped>
:global(.dialog-mensagem.p-dialog) { border: 1px solid #dbe5ef; border-radius: 1.25rem; overflow: hidden; box-shadow: 0 24px 80px #0f172a40; }
:global(.dialog-mensagem .p-dialog-header) { padding: 1.25rem 1.75rem; background: #f0f7f5; color: #334155; border-bottom: 1px solid #dcebe5; }
:global(.dialog-mensagem .p-dialog-content) { padding: 1.5rem 1.75rem; background: #fff; }
:global(.dialog-mensagem .p-dialog-footer) { padding: 1.1rem 1.75rem; background: #f0f7f5; border-top: 1px solid #dcebe5; }
.cabecalho-mensagem { display: flex; align-items: center; gap: 1rem; min-width: 0; }
.icone-comunicado { display: grid; place-items: center; flex-shrink: 0; width: 2.5rem; height: 2.5rem; border: 1px solid #e2e8f0; border-radius: .75rem; background: #fff; color: #64748b; }
.icone-comunicado i { font-size: 1.1rem; }
.titulo-comunicado { min-width: 0; overflow-wrap: anywhere; }
.titulo-comunicado h2 { margin: 0; font-size: clamp(1.1rem, 3vw, 1.35rem); font-weight: 600; line-height: 1.4; color: #334155; }
.metadados-mensagem { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .75rem; margin-bottom: 1.25rem; }
.selo-ciencia { display: inline-flex; align-items: center; gap: .45rem; padding: .4rem .7rem; border: 1px solid #fde68a; border-radius: 2rem; background: #fffbeb; color: #92400e; font-size: .75rem; font-weight: 600; }
.selo-ciencia.opcional { background: #eff6ff; border-color: #bfdbfe; color: #1e40af; }
.orientacao-leitura { color: #64748b; font-size: .8rem; }
.texto-mensagem { white-space: pre-wrap; overflow-wrap: anywhere; line-height: 1.8; color: #334155; max-height: 35vh; overflow: auto; padding-right: .5rem; }
.painel-leitura { margin-top: 1.5rem; padding: 1rem; border: 1px solid #dbeafe; border-radius: .8rem; background: #eff6ff; color: #1e40af; }
.status-leitura { display: flex; gap: .75rem; align-items: center; }
.icone-leitura { font-size: 1.2rem; }
.status-leitura strong { font-size: .85rem; }
.status-leitura p { margin: .25rem 0 0; font-size: .8rem; line-height: 1.5; }
.contador-leitura { margin-left: auto; font-size: 1.15rem; font-weight: 700; font-variant-numeric: tabular-nums; }
.trilha-leitura { height: .3rem; margin-top: .85rem; border-radius: 1rem; background: #dbeafe; overflow: hidden; }
.trilha-leitura > div { height: 100%; background: #3b82f6; border-radius: inherit; transition: width .2s linear; }
.painel-leitura.liberado { background: #ecfdf5; border-color: #a7f3d0; color: #065f46; }
.liberado .trilha-leitura > div { background: #10b981; }
.ciencia-mensagem { display: flex; align-items: center; gap: .75rem; margin-top: 1rem; padding: 1rem; border: 1px solid #cbd5e1; border-radius: .8rem; cursor: pointer; color: #334155; }
.ciencia-mensagem strong { font-size: .9rem; }
.ciencia-mensagem small { display: block; margin-top: .2rem; color: #64748b; font-size: .75rem; }
.ciencia-mensagem.bloqueada { background: #f8fafc; cursor: default; }
.ciencia-mensagem.confirmada { border-color: #34d399; background: #f0fdf4; }
.rodape-mensagem { display: flex; justify-content: space-between; align-items: center; gap: 1rem; width: 100%; }
.acoes-resposta { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: .65rem; }
.carregando-mensagem { text-align: center; padding: 2rem; color: #64748b; }
@media (max-width: 540px) {
  :global(.dialog-mensagem .p-dialog-header), :global(.dialog-mensagem .p-dialog-content), :global(.dialog-mensagem .p-dialog-footer) { padding: 1.1rem; }
  .icone-comunicado { display: none; }
  .rodape-mensagem { flex-direction: column-reverse; align-items: stretch; gap: .5rem; }
  .acoes-resposta > .p-button { flex: 1; }
}
@media (prefers-reduced-motion: reduce) { .trilha-leitura > div { transition: none; } }
</style>
