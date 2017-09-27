import { shallow } from 'vue-test-utils'
import Hmac from '@/components/HmacErrorRedirectPage.vue'

jest.useFakeTimers();

const wrapper = shallow(Hmac)

describe('Hmac.vue', () => {

  it('renders hmac error page', () => {
    expect(wrapper.hasClass('card_redirect_timer')).toBe(true)
  })

  it('expects redirect path set to be in url format', () => {
    expect(wrapper.vm.url).toEqual('https://www.weebly.com/home/')
  })

  it('expects that the timer countdown property is correctly set - countdown is 5', () => {
    let countdown = 5
    jest.runTimersToTime(1000);

    expect(wrapper.vm.timer).toEqual(countdown)
    expect(wrapper.vm.timer).not.toBeLessThan(countdown)
    expect(wrapper.vm.timer).not.toBeGreaterThan(countdown)
  })

})