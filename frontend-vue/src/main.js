import Vue from 'vue'
import App from './App.vue'
import store from './store/index.js'
import router from './router/index.js'
import './css/base.css'

Vue.config.productionTip = false

// Directiva para fechar dropdown ao clicar fora
Vue.directive('click-outside', {
  bind(el, binding) {
    el.clickOutsideEvent = function(event) {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value();
      }
    };
    document.addEventListener('click', el.clickOutsideEvent);
  },
  unbind(el) {
    document.removeEventListener('click', el.clickOutsideEvent);
  }
});

new Vue({
  router,
  store,
  render: h => h(App),
}).$mount('#app')
