import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

// https://vite.dev/config/
export default defineConfig({
  plugins : [vue()],
  resolve : {
    alias : {
      '@' : path.resolve(__dirname, './src'),
      //'@api' : path.resolve(__dirname, './src/core/api'),
    },
  },
  server : {
    host  : '0.0.0.0',
    port  : 5175,
    watch : {
      usePolling : true,
    }
  },
})
