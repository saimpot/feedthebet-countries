import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import EmptyState from '@/components/EmptyState.vue'

describe('EmptyState', () => {
  it('shows the query that matched nothing', () => {
    const wrapper = mount(EmptyState, { props: { query: 'xyz' } })
    expect(wrapper.text()).toContain('No countries match')
    expect(wrapper.text()).toContain('xyz')
  })
})
