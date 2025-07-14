import { defineStore } from 'pinia'

export const useTestStore = defineStore('test', {
  state : () => ({
    count        : 0,
    items        : [],
    selectedItem : null,
  }),

  getters : {
    doubleCount : (state) => state.count * 2,
    itemCount   : (state) => state.items.length,
  },

  actions : {
    increment() {
      this.count++
    },

    addItem(item) {
      this.items.push(item)
    },

    selectItem(item) {
      this.selectedItem = item
    },

    clearItems() {
      this.items = []
      this.selectedItem = null
    },
  },
})
