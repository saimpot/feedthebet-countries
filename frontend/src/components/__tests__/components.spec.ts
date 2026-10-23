import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import CountryCard from '@/components/CountryCard.vue'
import CountryGrid from '@/components/CountryGrid.vue'
import ErrorState from '@/components/ErrorState.vue'
import type { Country } from '@/types/country'

const germany: Country = { code: 'DE', name: 'Germany', flagUrl: 'de.svg', flagAlt: null }

describe('CountryCard', () => {
  it('renders the flag above the name with src and fallback alt', () => {
    const wrapper = mount(CountryCard, { props: { country: germany } })
    expect(wrapper.get('img').attributes()).toMatchObject({
      src: 'de.svg',
      alt: 'Flag of Germany',
      loading: 'lazy',
      decoding: 'async',
    })
    const html = wrapper.html()
    expect(html.indexOf('<img')).toBeLessThan(html.indexOf('<p'))
    expect(wrapper.get('p').text()).toBe('Germany')
  })

  it('prefers the provided flagAlt', () => {
    const country = { ...germany, flagAlt: 'Black red gold' }
    const wrapper = mount(CountryCard, { props: { country } })
    expect(wrapper.get('img').attributes('alt')).toBe('Black red gold')
  })
})

describe('CountryGrid', () => {
  it('renders one card per country', () => {
    const countries = [germany, { ...germany, code: 'FR', name: 'France' }]
    const wrapper = mount(CountryGrid, { props: { countries } })
    expect(wrapper.findAllComponents(CountryCard)).toHaveLength(2)
  })
})

describe('ErrorState', () => {
  it('shows the message and emits retry', async () => {
    const wrapper = mount(ErrorState, { props: { message: 'Nope' } })
    expect(wrapper.text()).toContain('Nope')
    await wrapper.get('button').trigger('click')
    expect(wrapper.emitted('retry')).toHaveLength(1)
  })
})
