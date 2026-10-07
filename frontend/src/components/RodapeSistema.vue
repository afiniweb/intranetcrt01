<script setup lang="ts">
import { ref } from 'vue'
import { version as versao } from '../../package.json'

const mostrandoVersao = ref(false)
</script>

<template>
  <footer class="rodape-sistema">
    <div class="marca-rodape">
      <img src="/images/logo-crt-01-alfa.png" alt="CRT-01" width="64" class="logo-rodape" />
      <span>Intranet - CRT-01</span>
    </div>
    <div class="versao-rodape" @keydown.esc="mostrandoVersao = false">
      <button
        type="button"
        class="botao-versao"
        aria-label="Versão do sistema"
        aria-describedby="tooltip-versao"
        @mouseenter="mostrandoVersao = true"
        @mouseleave="mostrandoVersao = false"
        @focus="mostrandoVersao = true"
        @blur="mostrandoVersao = false"
        @click="mostrandoVersao = true"
      >
        <i class="pi pi-info-circle" aria-hidden="true" />
      </button>
      <span v-show="mostrandoVersao" id="tooltip-versao" role="tooltip" class="tooltip-versao">
        Versão {{ versao }}
      </span>
    </div>
  </footer>
</template>

<style scoped>
.rodape-sistema {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: .75rem var(--recuo-lateral);
  border-top: 1px solid #dbe5ef;
  color: #475569;
  background: #fff;
  font-size: .8rem;
}
.marca-rodape { display: flex; align-items: center; gap: .75rem; }
.logo-rodape { display: block; width: 4rem; height: 2rem; object-fit: contain; }
.versao-rodape { position: relative; flex-shrink: 0; }
.botao-versao {
  display: grid;
  place-items: center;
  width: 2.75rem;
  height: 2.75rem;
  border: 0;
  border-radius: .5rem;
  color: #075985;
  background: #f0f9ff;
  cursor: pointer;
}
.botao-versao:hover { background: #e0f2fe; }
.botao-versao:focus-visible { outline: 2px solid #0284c7; outline-offset: 2px; }
.tooltip-versao {
  position: absolute;
  right: 0;
  bottom: calc(100% + .5rem);
  z-index: 20;
  padding: .5rem .75rem;
  border-radius: .4rem;
  color: #fff;
  background: #172033;
  white-space: nowrap;
  box-shadow: 0 4px 12px rgb(15 23 42 / 15%);
}
</style>
