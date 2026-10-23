import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import CountryCard from '@/components/CountryCard.vue'
import CountryGrid from '@/components/CountryGrid.vue'

describe('CountryGrid', () => {
  it('renders one card per country', () => {
    const countries = [
      { code: 'DE', name: 'Germany', flagUrl: 'de.svg', flagAlt: null },
      { code: 'FR', name: 'France', flagUrl: 'fr.svg', flagAlt: null },
    ]
    const wrapper = mount(CountryGrid, { props: { countries } })
    expect(wrapper.findAllComponents(CountryCard)).toHaveLength(2)
    expect(wrapper.findAll('ul > li')).toHaveLength(2)
  })

  it('hides cards outside the visible set without unmounting them', () => {
    const countries = [
      { code: 'DE', name: 'Germany', flagUrl: 'de.svg', flagAlt: null },
      { code: 'FR', name: 'France', flagUrl: 'fr.svg', flagAlt: null },
    ]
    const wrapper = mount(CountryGrid, { props: { countries, visible: new Set(['FR']) } })
    const items = wrapper.findAll<HTMLElement>('li')
    expect(items).toHaveLength(2)
    expect(
      items.filter((li) => li.element.style.display !== 'none').map((li) => li.text()),
    ).toEqual(['France'])
  })
})
