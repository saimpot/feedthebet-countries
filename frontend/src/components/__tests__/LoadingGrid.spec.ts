import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import LoadingGrid from '@/components/LoadingGrid.vue'

describe('LoadingGrid', () => {
  it('renders the requested number of skeleton cards, hidden from assistive tech', () => {
    const wrapper = mount(LoadingGrid, { props: { count: 4 } })
    expect(wrapper.findAll('[data-testid="skeleton-card"]')).toHaveLength(4)
    expect(wrapper.attributes('aria-hidden')).toBe('true')
  })
})
