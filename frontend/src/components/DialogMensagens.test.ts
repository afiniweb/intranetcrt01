// @vitest-environment jsdom
import { flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import DialogMensagens from './DialogMensagens.vue'
import { mensagemService, type Mensagem } from '../services/mensagemService'
vi.mock('../services/mensagemService', () => ({ mensagemService: { pendente: vi.fn(), apresentar: vi.fn(), responder: vi.fn() } }))
const mensagem: Mensagem = { id: 1, instituicaoId: 1, instituicaoNome: 'Instituição', titulo: 'Aviso', texto: 'Leia este aviso', inicioEm: '', fimEm: '', obrigatoria: true, excluida: false, versao: 1 }
const stubs = {
  Dialog: { template: '<div><slot/><slot name="footer"/></div>' },
  Button: { props: ['label', 'disabled'], template: '<button :disabled="disabled">{{ label }}</button>' },
  Checkbox: { props: ['modelValue', 'disabled'], emits: ['update:modelValue'], template: '<input type="checkbox" :disabled="disabled" :checked="modelValue" @change="$emit(\'update:modelValue\', $event.target.checked)" />' },
  Message: { template: '<div><slot/></div>' },
  ProgressSpinner: true,
}
describe('Diálogo de mensagens', () => {
  beforeEach(() => { vi.useFakeTimers(); vi.resetAllMocks() })
  afterEach(() => { vi.useRealTimers() })
  it('bloqueia a ciência por 30 segundos e exige marcar o checkbox', async () => {
    vi.mocked(mensagemService.pendente).mockResolvedValueOnce(mensagem).mockResolvedValueOnce(null)
    vi.mocked(mensagemService.apresentar).mockResolvedValue({ mensagem, restantes: 30 })
    const wrapper = mount(DialogMensagens, { global: { stubs } })
    await flushPromises()
    expect(wrapper.get('input').attributes('disabled')).toBeDefined()
    expect(wrapper.text()).toContain('30 segundos')
    await vi.advanceTimersByTimeAsync(29000)
    expect(wrapper.get('input').attributes('disabled')).toBeDefined()
    await vi.advanceTimersByTimeAsync(1000)
    expect(wrapper.get('input').attributes('disabled')).toBeUndefined()
    const continuar = wrapper.findAll('button').find(b => b.text() === 'Continuar')!
    expect(continuar.attributes('disabled')).toBeDefined()
    await wrapper.get('input').setValue(true)
    await continuar.trigger('click'); await flushPromises()
    expect(mensagemService.responder).toHaveBeenCalledWith(mensagem, true)
    expect(wrapper.emitted('concluido')).toHaveLength(1)
    wrapper.unmount()
  })
  it('também bloqueia a dispensa opcional por 30 segundos', async () => {
    const opcional = { ...mensagem, obrigatoria: false }
    vi.mocked(mensagemService.pendente).mockResolvedValueOnce(opcional).mockResolvedValueOnce(null)
    vi.mocked(mensagemService.apresentar).mockResolvedValue({ mensagem: opcional, restantes: 30 })
    const wrapper = mount(DialogMensagens, { global: { stubs } })
    await flushPromises()
    const dispensar = wrapper.findAll('button').find(b => b.text() === 'Agora não')!
    expect(dispensar.attributes('disabled')).toBeDefined()
    await vi.advanceTimersByTimeAsync(30000)
    await dispensar.trigger('click'); await flushPromises()
    expect(mensagemService.responder).toHaveBeenCalledWith(opcional, false)
    wrapper.unmount()
  })
  it('não libera o sistema se a consulta falhar', async () => {
    vi.mocked(mensagemService.pendente).mockRejectedValue(new Error('indisponível'))
    const wrapper = mount(DialogMensagens, { global: { stubs } })
    await flushPromises()
    expect(wrapper.emitted('concluido')).toBeUndefined()
    expect(wrapper.text()).toContain('Tente novamente')
    wrapper.unmount()
  })
})
