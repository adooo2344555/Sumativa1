<script setup>
import { computed, onMounted, ref } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import { Swiper, SwiperSlide } from "swiper/vue";
import "swiper/css";
import "swiper/css/navigation";
import { Autoplay, Navigation } from "swiper/modules";

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faBars, faShoppingCart } from "@fortawesome/free-solid-svg-icons";
import axios from "axios";
import Swal from "sweetalert2";

const page = usePage();
const user = page.props.auth.user;

const productos = ref([]);

const destacados = ref([
  {
    id: 1,
    nombre: "Aretes",
    precio: 500,
    imagen: "/images/slider/aretes.webp",
  },
  {
    id: 2,
    nombre: "Pulseras",
    precio: 400,
    imagen: "/images/slider/pulseras.webp",
  },
  {
    id: 3,
    nombre: "Anillos",
    precio: 1000,
    imagen: "/images/slider/anillos.jpg",
  },
]);

const categorias = ref([]);
const filtroCategoria = ref("");
const searchQuery = ref("");
const isOpen = ref(false);
const urlBase = "http://localhost:8000/api/";
const reservacion = ref({
  id: null,
  fecha: new Date().toISOString().split("T")[0],
  estado: "P",
  total: 0.0,
  user: { ...user },
  detalleReservaciones: [],
});
const cantidades = ref({});

onMounted(() => {
  fetchCategorias();
  fetchProductos();
});

const fetchCategorias = async () => {
  try {
    const response = await axios.get(`${urlBase}categorias`);
    categorias.value = response.data;
  } catch (err) {
    console.error("error", err);
  }
};

const fetchProductos = async () => {
  try {
    const response = await axios.get(`${urlBase}productos`);
    productos.value = response.data;
  } catch (err) {
    console.error("error", err);
  }
};

const agregarReservacion = (producto, cantidad) => {
  if (!user) {
    Swal.fire("Debes estar autenticado para realizar una reservación.");
    return;
  }
  const nuevoProducto = { ...producto };
  const existe = reservacion.value.detalleReservaciones.find(
    (item) => item.producto.id === producto.id
  );
  if (existe) {
    existe.cantidad += cantidad;
    Swal.fire({
      title: "Agregado al carrito exitosamente",
      text: `Se ha incrementado ${cantidad} unidades del producto ${producto.nombre}`,
      icon: "success",
    });
  } else {
    reservacion.value.detalleReservaciones.push({
      cantidad: cantidad,
      precio: producto.precio,
      producto: nuevoProducto,
      subtotal: producto.precio * cantidad,
    });
    Swal.fire({
      title: "Agregado al carrito exitosamente",
      text: `Se ha agregado ${cantidad} ${producto.nombre}`,
      icon: "success",
    });
  }
  reservacion.value.total = totalReservacion;
};

const confirmarReservacion = async () => {
  Swal.fire({
    title: "¿Estás seguro(a)?",
    text: "Deseas confirmar la reservación, después no se podrá revertir",
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    cancelButtonText: "No",
    confirmButtonText: "¡Sí, confirmar!",
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        const response = await axios.post(`${urlBase}reservaciones`, reservacion.value);
        const { message, reservacion: nuevaReservacion } = response.data;
        const fechaFormateada = new Date(nuevaReservacion.fecha).toLocaleDateString();
        Swal.fire({
          title: "¡Reservación Exitosamente!",
          text: `${message} con el número ${nuevaReservacion.id} para el ${fechaFormateada}`,
          icon: "success",
        });
        reservacion.value = {
          id: null,
          fecha: new Date().toISOString().split("T")[0],
          estado: "P",
          user: user,
          detalleReservaciones: [],
        };
          // Cerrar el modal automáticamente
          cerrarModal();
      } catch (err) {
        console.error("Error al confirmar la reservacion", err);
        Swal.fire({
          title: "Error",
          text: "Hubo un problema al confirmar la reservación.",
          icon: "error",
        });
      }
    }
  });
};

const filteredProducts = computed(() => {
  return productos.value.filter((producto) => {
    return (
      (!searchQuery.value ||
        producto.nombre
          .toLowerCase()
          .includes(searchQuery.value.toLocaleLowerCase())) &&
      (!filtroCategoria.value ||
        producto.categoria.nombre === filtroCategoria.value)
    );
  });
});

const totalReservacion = computed(() => {
  return reservacion.value.detalleReservaciones.reduce(
    (total, item) => total + item.producto.precio * item.cantidad,
    0
  );
});

const deleteItem = (item) => {
  const index = reservacion.value.detalleReservaciones.indexOf(item);
  reservacion.value.detalleReservaciones.splice(index, 1);
};

const isModalOpen = ref(false);

const abrirModal = () => {
  isModalOpen.value = true;
};

const cerrarModal = () => {
  isModalOpen.value = false;
};
</script>

<template>
  <div class="container mx-auto p-6 bg-white text-gray-800">
    <nav class="fixed top-0 left-0 w-full bg-yellow-500 text-white shadow-md z-50">
      <div class="container mx-auto flex flex-wrap justify-between items-center p-4">
        <h1 class="text-xl font-bold">Joyeria Aurum</h1>
        <button @click="isOpen = !isOpen" class="lg:hidden text-white focus:outline-none">
          <FontAwesomeIcon :icon="faBars" class="w-6 h-6 text-white" />
        </button>
        <div :class="{ hidden: !isOpen, block: isOpen }" class="w-full lg:w-auto lg:flex lg:items-center">
          <div v-if="!user" class="flex flex-col lg:flex-row lg:space-x-4 text-center mt-4 lg:mt-0">
            <a href="/login" class="text-white py-2 lg:py-0">Iniciar Sesión</a>
            <a href="/register" class="text-white py-2 lg:py-0">Registrarse</a>
          </div>
          <div v-else class="flex flex-col lg:flex-row lg:items-center lg:space-x-4 text-center mt-4 lg:mt-0">
            <div class="flex items-center space-x-4">
              <span class="py-2 lg:py-0">{{ user.name }}</span>
              <button @click="abrirModal" class="relative bg-yellow-500 text-white p-2 rounded-full shadow-lg hover:bg-white-600">
                <FontAwesomeIcon :icon="faShoppingCart" class="w-5 h-5" />
                <span v-if="reservacion.detalleReservaciones.length > 0" class="absolute top-0 right-0 bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5">
                  {{ reservacion.detalleReservaciones.length }}
                </span>
              </button>
            </div>
            <Link :href="route('logout')" method="post" as="button" class="bg-red-500 px-4 py-2 rounded w-full lg:w-auto">Cerrar Sesión</Link>
          </div>
        </div>
      </div>
    </nav>

    <div class="pt-8">
      <Swiper :modules="[Navigation, Autoplay]" navigation :autoplay="{ delay: 3000, disableOnInteraction: false }" :speed="1000" loop class="my-6">
        <SwiperSlide v-for="destacado in destacados" :key="destacado.id" class="p-4">
          <div class="bg-white text-black rounded-lg overflow-hidden shadow-lg">
            <img :src="destacado.imagen" class="w-full h-96 object-cover object-center" />
            <div class="p-4">
              <h2 class="text-xl font-semibold">{{ destacado.nombre }}</h2>
              <p class="text-yellow-400 text-lg font-bold">${{ destacado.precio }}</p>
            </div>
          </div>
        </SwiperSlide>
      </Swiper>

      <div class="flex flex-wrap justify-between items-center my-6">
        <input type="text" v-model="searchQuery" placeholder="Buscar..." class="border p-2 rounded w-full md:w-1/3 mb-2 md:mb-0" />
        <select v-model="filtroCategoria" class="border p-2 rounded w-full md:w-auto mb-2 md:mb-0">
          <option value="">Categoría</option>
          <option v-for="cat in categorias" :key="cat">{{ cat.nombre }}</option>
        </select>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div v-for="producto in filteredProducts" :key="producto.id" class="bg-white rounded-lg shadow-lg overflow-hidden p-4">
  <Swiper :modules="[Navigation]" navigation class="h-40">
    <SwiperSlide v-for="img in producto.imagenes" :key="img">
      <img :src="`images/products/${img.nombre}`" class="w-full h-40 object-contain" />
    </SwiperSlide>
  </Swiper>
  <div class="p-4">
    <h2 class="text-xl font-semibold">{{ producto.nombre }}</h2>
    <span class="text-sm text-gray-800">
      {{ producto.descripcion }}
      <br />
      <strong>Material:</strong> {{ producto.material }}
    </span>
    <p class="text-yellow-500 text-lg font-bold">${{ producto.precio }}</p>
    <div class="flex flex-col md:flex-row md:space-x-4">
      <input type="number" min="1" :value="cantidades[producto.id] || 1" @input="cantidades[producto.id] = Number($event.target.value)" class="border p-2 rounded w-full my-2 md:w-auto md:my-0" />
      <button @click="agregarReservacion(producto, cantidades[producto.id] || 1)" class="bg-yellow-500 text-white px-4 py-2 rounded w-full md:w-auto">Agregar al Carrito</button>
    </div>
  </div>
</div>
      </div>

      <div class="mt-6 flex justify-center">
        <button class="px-4 py-2 bg-gray-700 text-white rounded">Anterior</button>
        <button class="px-4 py-2 bg-gray-700 text-white rounded ml-4">Siguiente</button>
      </div>
    </div>

    <!--Modal del carrito-->

    <div v-if="isModalOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-3/4 lg:w-1/2 p-6 max-h-[80vh] overflow-y-auto">
        <h2 class="text-2xl font-semibold mb-4 text-gray-700">Resumen de la Reservación</h2>
        <div class="mt-4">
          <label for="fechaReservacion" class="block text-sm font-medium text-gray-700">Fecha de Reservación</label>
          <input type="date" id="fechaReservacion" v-model="reservacion.fecha" class="mt-1 block w-full p-2 border border-gray-300 rounded-md">
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full bg-white border-gray-300 shadow-md rounded-lg">
            <thead class="bg-yellow-200 text-gray-700">
              <tr>
                <th class="px-4 py-2 text-left">Producto</th>
                <th class="px-4 py-2 text-left">Categoría</th>
                <th class="px-4 py-2 text-right">Precio</th>
                <th class="px-4 py-2 text-right">Cantidad</th>
                <th class="px-4 py-2 text-right">Total</th>
                <th class="px-4 py-2 text-center">Acción</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in reservacion.detalleReservaciones" :key="item.id" class="border-b">
                <td class="px-4 py-2 text-left">{{ item.producto.nombre }} - {{ item.producto.descripcion }}</td>
                <td class="px-4 py-2 text-left">{{ item.producto.categoria.nombre }}</td>
                <td class="px-4 py-2 text-right">${{ item.producto.precio }}</td>
                <td class="px-4 py-2 text-right">{{ item.cantidad }}</td>
                <td class="px-4 py-2 text-right">${{ ((item.producto.precio ?? 0) * item.cantidad).toFixed(2) }}</td>
                <td class="px-4 py-2 text-center">
                  <button @click="deleteItem(item)" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-700">x</button>
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-100 font-semibold">
              <tr>
                <td></td>
                <td></td>
                <td></td>
                <td colspan="6" class="px-4 py-3 text-right">Total</td>
                <td class="px-4 py-3 text-right text-bold text-blue-600">${{ (totalReservacion ?? 0).toFixed(2) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
        <div class="mt-4 text-right">
          <button @click="confirmarReservacion" class="px-6 py-3 bg-yellow-500 hover:bg-yellow-500 text-white rounded font-semibold">Confirmar Reservacion</button>
          <button @click="cerrarModal" class="ml-4 px-6 py-3 bg-gray-500 hover:bg-gray-700 text-white rounded font-semibold">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
</template>