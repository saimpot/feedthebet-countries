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

  it('shows a skeleton until the flag has loaded, then fades the image in', async () => {
    const wrapper = mount(CountryCard, { props: { country: germany } })
    expect(wrapper.find('[data-testid="flag-skeleton"]').exists()).toBe(true)
    expect(wrapper.get('img').classes()).toContain('opacity-0')

    await wrapper.get('img').trigger('load')
    expect(wrapper.find('[data-testid="flag-skeleton"]').exists()).toBe(false)
    expect(wrapper.get('img').classes()).toContain('opacity-100')
  })

  it('replaces a broken flag with a fallback', async () => {
    const wrapper = mount(CountryCard, { props: { country: germany } })
    await wrapper.get('img').trigger('error')

    expect(wrapper.find('img').exists()).toBe(false)
    expect(wrapper.find('[data-testid="flag-skeleton"]').exists()).toBe(false)
    expect(wrapper.get('[data-testid="flag-fallback"]').attributes('aria-label')).toBe(
      'Flag of Germany unavailable',
    )
  })

  it('shows the skeleton again when the flag URL changes', async () => {
    const wrapper = mount(CountryCard, { props: { country: germany } })
    await wrapper.get('img').trigger('load')
    await wrapper.setProps({ country: { ...germany, flagUrl: 'de.png' } })

    expect(wrapper.find('[data-testid="flag-skeleton"]').exists()).toBe(true)
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
