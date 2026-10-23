import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import AppHeader from '@/components/AppHeader.vue'

const user = { name: 'Ann Lee', email: 'ann@example.com', picture: 'ann.png' }

describe('AppHeader', () => {
  it('renders the title and the user name, with a decorative avatar', () => {
    const wrapper = mount(AppHeader, { props: { title: 'Countries', user } })
    expect(wrapper.get('h1').text()).toBe('Countries')
    expect(wrapper.text()).toContain('Ann Lee')
    const avatar = wrapper.get('img')
    expect(avatar.attributes('src')).toBe('ann.png')
    expect(avatar.attributes('alt')).toBe('')
  })

  it('emits logout when the button is clicked', async () => {
    const wrapper = mount(AppHeader, { props: { title: 'Countries', user } })
    await wrapper.get('button').trigger('click')
    expect(wrapper.emitted('logout')).toHaveLength(1)
  })

  it('renders no user controls without a user', () => {
    const wrapper = mount(AppHeader, { props: { title: 'Countries', user: null } })
    expect(wrapper.find('img').exists()).toBe(false)
    expect(wrapper.find('button').exists()).toBe(false)
  })
})
