import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import ErrorState from '@/components/ErrorState.vue'

describe('ErrorState', () => {
  it('announces the message and emits retry', async () => {
    const wrapper = mount(ErrorState, { props: { message: 'Nope' } })
    expect(wrapper.get('[role="alert"]').text()).toContain('Nope')
    await wrapper.get('button').trigger('click')
    expect(wrapper.emitted('retry')).toHaveLength(1)
  })
})
