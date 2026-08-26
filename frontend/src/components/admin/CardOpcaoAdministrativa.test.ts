// @vitest-environment jsdom
import { shallowMount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import CardOpcaoAdministrativa from './CardOpcaoAdministrativa.vue'
describe('CardOpcaoAdministrativa', () => {
  it('exibe os dados da opção e emite a seleção', async () => {
    const wrapper = shallowMount(CardOpcaoAdministrativa, {
      props: { titulo: 'Instituições', descricao: 'Cadastre instituições.', icone: 'pi pi-building', cor: '#2563eb' },
      global: { stubs: {
        Card: { template: '<section><slot name="header"/><slot name="title"/><slot name="content"/><slot name="footer"/></section>' },
        Button: { template: "<button>Acessar cadastro</button>" },
      }},
    })
    expect(wrapper.text()).toContain('Instituições')
    expect(wrapper.text()).toContain('Cadastre instituições.')
    await wrapper.get('button').trigger('click')
    expect(wrapper.emitted('selecionar')).toHaveLength(1)
  })
})
