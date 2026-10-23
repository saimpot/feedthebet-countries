import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import SearchInput from '@/components/SearchInput.vue'

describe('SearchInput', () => {
  it('has an accessible label bound to the input', () => {
    const wrapper = mount(SearchInput, { props: { modelValue: '' } })
    const input = wrapper.get('input')
    const label = wrapper.get(`label[for="${input.attributes('id')}"]`)
    expect(label.text()).toBe('Search countries')
  })

  it('emits update:modelValue as the user types', async () => {
    const wrapper = mount(SearchInput, { props: { modelValue: '' } })
    await wrapper.get('input').setValue('fra')
    expect(wrapper.emitted('update:modelValue')).toEqual([['fra']])
  })

  it('reflects the model value and can be disabled', () => {
    const wrapper = mount(SearchInput, { props: { modelValue: 'de', disabled: true } })
    expect(wrapper.get('input').element.value).toBe('de')
    expect(wrapper.get('input').attributes('disabled')).toBeDefined()
  })
})
