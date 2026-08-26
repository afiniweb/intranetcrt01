<script setup lang="ts">
import { ref } from 'vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Password from 'primevue/password'
import type { UsuarioDto } from '../../dto/usuario/UsuarioDto'
import { autenticacaoService } from '../../services/autenticacaoService'
const emit = defineEmits<{ autenticado: [usuario: UsuarioDto] }>()
const email = ref(''), senha = ref(''), loading = ref(false), erro = ref('')
async function entrar(): Promise<void> { loading.value = true; erro.value = ''; try { emit('autenticado', await autenticacaoService.login(email.value, senha.value)) } catch { erro.value = 'E-mail ou senha inválidos, ou usuário inativo.' } finally { loading.value = false } }
</script>
<template>
  <main class="pagina-login"><section class="card-login"><div class="simbolo-marca" aria-hidden="true">CRT</div><div><p class="identificador-secao">INTRANET CRT-01</p><h1>Acesse sua conta</h1><p>Entre com as credenciais institucionais.</p></div><Message v-if="erro" severity="error">{{ erro }}</Message><form class="formulario-dialog" @submit.prevent="entrar"><label>E-mail <InputText v-model="email" type="email" autocomplete="username" required autofocus /></label><label>Senha <Password v-model="senha" :feedback="false" toggle-mask autocomplete="current-password" required /></label><Button type="submit" label="Entrar" icon="pi pi-sign-in" :loading="loading" /></form></section></main>
</template>
