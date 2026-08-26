<script setup lang="ts">
import Button from 'primevue/button'
import Card from 'primevue/card'
defineProps<{ titulo: string; descricao: string; icone: string; cor: string }>()
const emit = defineEmits<{ selecionar: [] }>()
</script>
<template>
  <Card class="card-opcao-administrativa">
    <template #header><div class="icone-opcao" :style="{ '--cor-opcao': cor }"><i :class="icone" aria-hidden="true" /></div></template>
    <template #title>{{ titulo }}</template>
    <template #content><p class="descricao-opcao">{{ descricao }}</p></template>
    <template #footer>
      <Button label="Acessar cadastro" icon="pi pi-arrow-right" icon-pos="right" severity="secondary" text fluid :aria-label="`Acessar cadastro de ${titulo}`" @click="emit('selecionar')" />
    </template>
  </Card>
</template>
