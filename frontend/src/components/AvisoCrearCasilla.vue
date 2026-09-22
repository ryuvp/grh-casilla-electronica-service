<template>
  <!-- Sin marcado propio: solo dispara el SweetAlert de aviso/opt-in. -->
</template>

<script setup>
import { onMounted } from 'vue'
import Swal from 'sweetalert2/dist/sweetalert2.js'
import { MiCasilla } from '@/core/models'

const SESSION_KEY = 'aviso_crear_casilla_pospuesto'

async function verificarYPreguntar() {
  // Ya se lo preguntamos y dijo "ahora no" en esta sesión: no insistir en
  // cada navegación, pero sí se le vuelve a preguntar en su próximo ingreso.
  if (sessionStorage.getItem(SESSION_KEY)) return

  try {
    const { data } = await MiCasilla.estado()
    if (data?.tiene_casilla) return

    const { isConfirmed } = await Swal.fire({
      icon: 'question',
      title: '¿Quieres crear tu casilla electrónica?',
      html: 'Todavía no tienes una casilla electrónica propia. La casilla te permite recibir notificaciones y documentos oficiales de forma digital.<br><br><small class="text-muted">Nadie puede crearla por ti: solo tú puedes decidirlo.</small>',
      showCancelButton: true,
      confirmButtonText: 'Sí, crear mi casilla',
      cancelButtonText: 'Ahora no',
      reverseButtons: true,
    })

    if (isConfirmed) {
      await MiCasilla.crear()
      await Swal.fire({
        icon: 'success',
        title: 'Casilla creada',
        text: 'Tu casilla electrónica ya está activa.',
        timer: 2500,
        showConfirmButton: false,
      })
    } else {
      sessionStorage.setItem(SESSION_KEY, '1')
    }
  } catch (error) {
    // Silencioso: este aviso es una mejora de UX, no debe romper la carga del
    // resto de la aplicación si /mi-casilla/estado falla puntualmente.
    console.error('No se pudo verificar el estado de la casilla propia:', error)
  }
}

onMounted(verificarYPreguntar)
</script>
