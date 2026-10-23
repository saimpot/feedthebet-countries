import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import CountryCard from '@/components/CountryCard.vue'
import type { Country } from '@/types/country'

const germany: Country = { code: 'DE', name: 'Germany', flagUrl: 'de.svg', flagAlt: null }
const skeleton = '[data-testid="flag-skeleton"]'

describe('CountryCard', () => {
  it('renders the flag above the name with src and fallback alt', () => {
    const wrapper = mount(CountryCard, { props: { country: germany } })
    const img = wrapper.get('img')
    expect(img.attributes()).toMatchObject({
      src: 'de.svg',
      alt: 'Flag of Germany',
      loading: 'lazy',
      decoding: 'async',
    })
    const name = wrapper.get('p')
    expect(name.text()).toBe('Germany')
    expect(
      img.element.compareDocumentPosition(name.element) & Node.DOCUMENT_POSITION_FOLLOWING,
    ).toBeTruthy()
  })

  it('prefers the provided flagAlt', () => {
    const country = { ...germany, flagAlt: 'Black red gold' }
    const wrapper = mount(CountryCard, { props: { country } })
    expect(wrapper.get('img').attributes('alt')).toBe('Black red gold')
  })

  it('shows a skeleton until the flag has loaded', async () => {
    const wrapper = mount(CountryCard, { props: { country: germany } })
    expect(wrapper.find(skeleton).exists()).toBe(true)

    await wrapper.get('img').trigger('load')
    expect(wrapper.find(skeleton).exists()).toBe(false)
    expect(wrapper.find('img').exists()).toBe(true)
  })

  it('replaces a broken flag with an accessible fallback', async () => {
    const wrapper = mount(CountryCard, { props: { country: germany } })
    await wrapper.get('img').trigger('error')

    expect(wrapper.find('img').exists()).toBe(false)
    expect(wrapper.find(skeleton).exists()).toBe(false)
    expect(wrapper.get('[role="img"]').attributes('aria-label')).toBe('Flag of Germany unavailable')
  })

  it('shows the skeleton again when the flag URL changes', async () => {
    const wrapper = mount(CountryCard, { props: { country: germany } })
    await wrapper.get('img').trigger('load')
    await wrapper.setProps({ country: { ...germany, flagUrl: 'de.png' } })

    expect(wrapper.find(skeleton).exists()).toBe(true)
  })
})
