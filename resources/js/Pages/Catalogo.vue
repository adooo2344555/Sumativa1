<script setup>
import { computed, onMounted, ref } from "vue"; //para gestionar los estados de las variables
import { usePage, Link } from "@inertiajs/vue3"; //para la navegación
import { Swiper, SwiperSlide } from "swiper/vue"; //para el slide
import "swiper/css";
import "swiper/css/navigation";
import { Autoplay, Navigation } from "swiper/modules";

import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faBars } from "@fortawesome/free-solid-svg-icons"; //para la iconografía
import axios from "axios";
import Swal from "sweetalert2";

const page = usePage(); //de aquí se está obteniendo el usuario
const user = page.props.auth.user;

const productos = ref([]); //se eliminó las imagenes estáticas

const destacados = ref([
  //imagenes del slider (los productos destacados)
  {
    id: 1,nombre: "Aretes",precio: 500,imagen: "/images/slider/aretes.webp",
  },
  {
    id: 2,nombre: "Pulseras",
    precio: 400,
    imagen: "/images/slider/pulseras.jpeg",
  },
  {
    id: 3,
    nombre: "Anillos",
    precio: 1000,
    imagen: "/images/slider/anillos.jpg",
  },
]);

const categorias = ref([]);
/* const marcas = ref([]);
 */const filtroCategoria = ref("");
/* const filtroMarca = ref("");
 */const searchQuery = ref("");
const isOpen = ref(false);
const urlBase = "http://localhost:8000/api/";
const reservacion = ref({
  id: null,
  fecha: new Date().toISOString().split("T")[0], //fomato yyyy-MM-dd
  estado: "R",
  total: 0.0,
  user: { ...user },
  detalleReservaciones: [],
});
const cantidades = ref({}); //objeto para almacenar temp cantidades

//se accede al evento, el cual tiene que estar importado en la parte superior
//desde aquí se están llamando las funciones definidas como peticiones
onMounted(() => {
/*   fetchMarcas();
 */  fetchCategorias();
  fetchProductos();
});

//funciones para hacer consultas a la API
//con estas peticiones se limita el colocar el nombre de la entidad en la URL por ejemplo:
//http://localhost:8000/api/marcas

/* const fetchMarcas = async () => {
  try {
    const response = await axios.get(`${urlBase}marcas`);
    marcas.value = response.data;
  } catch (err) {
    console.error("error", err);
  }
}; */

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
//fin de las peticiones

const agregarReservacion = (producto, cantidad) => {
  if (!user) {
    Swal.fire("Debes estar autenticado para realizar una orden.");
    return;
  }
  //hacemos el proceso para llenar el detalle de la orden
  const nuevoProducto = { ...producto };
  const existe = reservacion.value.detalleReservaciones.find(
    (item) => item.producto.id === producto.id
  );
  if (existe) {
    existe.cantidad += cantidad;
    Swal.fire({
      title: "Producto Agregado",
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
      title: "Producto Agregado",
      text: `Se ha agregado ${cantidad} ${producto.nombre}`,
      icon: "success",
    });
  }
  reservacion.value.total = totalReservacion;
};

//función para enviar a guardar la orden
const confirmarReservacion = async () => {
  Swal.fire({
    title: "¿Estás seguro(a)?",
    text: "Deseas confirmar la orden, después no se podrá revertir",
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
        //desestructuramos la respuesta del backend
        const { message, reservacion: nuevaReservacion } = response.data;
        Swal.fire({
          title: "¡Confirmar!",
          text: `${message} con el número ${nuevaReservacion}`,
          icon: "success",
        });
        //seteamos el objeto orden
        reservacion.value = {
          id: null,
          fecha: new Date().toISOString().split("T")[0],
          estado: "R",
          user: user,
          detalleReservaciones: [],
        };
      } catch (err) {
        console.error("Error al confirmar la reservacion, err");
      }
    }
  });
};

//propiedad computable para filtrar los productos
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

//propiedad computable para calcular el total de la orden
const totalReservacion = computed(() => {
  return reservacion.value.detalleReservaciones.reduce(
    (total, item) => total + item.producto.precio * item.cantidad,
    0
  );
});

//funcion para eliminar un producto de la orden
const deleteItem = (item) => {
  const index = reservacion.value.detalleReservaciones.indexOf(item);
  reservacion.value.detalleReservaciones.splice(index, 1);
};
</script>

<template>
  <div class="container mx-auto p-6">
    <nav
      class="fixed top-0 left-0 w-full bg-blue-700 text-white shadow-md z-50"
    >
      <div
        class="container mx-auto flex flex-wrap justify-between items-center p-4"
      >
        <h1 class="text-xl font-bold">Joyeria Aurum</h1>
        <!-- Botón menú hamburguesa -->
        <button
          @click="isOpen = !isOpen"
          class="lg:hidden text-white focus:outline-none"
        >
          <FontAwesomeIcon :icon="faBars" class="w-6 h-6 text-white" />
        </button>

        <!-- Menú principal -->
        <div
          :class="{ hidden: !isOpen, block: isOpen }"
          class="w-full lg:w-auto lg:flex lg:items-center"
        >
          <div
            v-if="!user"
            class="flex flex-col lg:flex-row lg:space-x-4 text-center mt-4 lg:mt-0"
          >
            <a href="/login" class="text-white py-2 lg:py-0">Iniciar Sesión</a>
            <a href="/register" class="text-white py-2 lg:py-0">Registrarse</a>
          </div>

          <div
            v-else
            class="flex flex-col lg:flex-row lg:items-center lg:space-x-4 text-center mt-4 lg:mt-0"
          >
            <span class="py-2 lg:py-0">{{ user.name }}</span>
            <Link
              :href="route('logout')"
              method="post"
              as="button"
              class="bg-red-500 px-4 py-2 rounded w-full lg:w-auto"
              >Cerrar Sesión</Link
            >
            <!--<button class="bg-red-500 px-4 py-2 rounded w-full lg:w-auto"></button>-->
          </div>
        </div>
      </div>
    </nav>

    <div class="pt-8">
      <Swiper
        :modules="[Navigation, Autoplay]"
        navigation
        :autoplay="{ delay: 3000, disableOnInteraction: false }"
        :speed="1000"
        loop
        class="my-6"
      >
        <SwiperSlide
          v-for="destacado in destacados"
          :key="destacado.id"
          class="p-4"
        >
          <div
            class="bg-white-800 text-white rounded-lg overflow-hidden shadow-lg"
          >
            <img
              :src="destacado.imagen"
              class="w-full h-56 object-cover object-center"
            />
            <div class="p-4">
              <h2 class="text-xl font-semibold">{{ destacado.nombre }}</h2>
              <p class="text-yellow-400 text-lg font-bold">
                ${{ destacado.precio }}
              </p>
            </div>
          </div>
        </SwiperSlide>
      </Swiper>

      <div class="flex flex-wrap justify-between items-center my-6">
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Buscar..."
          class="border p-2 rounded w-full md:w-1/3 mb-2 md:mb-0"
        />
        <select
          v-model="filtroCategoria"
          class="border p-2 rounded w-full md:w-auto mb-2 md:mb-0"
        >
          <option value="">Categoría</option>
          <option v-for="cat in categorias" :key="cat">{{ cat.nombre }}</option>
        </select>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div
          v-for="producto in filteredProducts"
          :key="producto.id"
          class="bg-white rounded-lg shadow-lg overflow-hidden p-4"
        >
          <Swiper :modules="[Navigation]" navigation class="h-40">
            <SwiperSlide v-for="img in producto.imagenes" :key="img">
              <img
                :src="`images/products/${img.nombre}`"
                class="w-full h-40 object-contain"
              />
              <!--se buscan las 
            imagenes dentro de la carpeta pública-->
            </SwiperSlide>
          </Swiper>
          <div class="p-4">
            <h2 class="text-xl font-semibold">{{ producto.nombre }}</h2>
            <span class="text-sm text-blue-600">{{
              producto.descripcion
            }}</span>
            <p class="text-yellow-500 text-lg font-bold">
              ${{ producto.precio }}
            </p>
            <div class="flex flex-col md:flex-row md:space-x-4">
              <input
                type="number"
                min="1"
                :value="cantidades[producto.id] || 1"
                @input="cantidades[producto.id] = Number($event.target.value)"
                class="border p-2 rounded w-full my-2 md:w-auto md:my-0"
              />
              <button
                @click="agregarReservacion(producto, cantidades[producto.id] || 1)"
                class="bg-blue-500 text-white px-4 py-2 rounded w-full md:w-auto"
              >
                Agregar Reservacion
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-6 flex justify-center">
        <button class="px-4 py-2 bg-gray-700 text-white rounded">
          Anterior
        </button>
        <button class="px-4 py-2 bg-gray-700 text-white rounded ml-4">
          Siguiente
        </button>
      </div>
    </div>
    <!--Div para mostrar el datelle de la reservacion-->
    <div
      v-if="reservacion.detalleReservaciones?.length > 0"
      class="mt-6 p-6 bg-white rounded-lg shadow-lg"
    >
      <h2 class="text-2xl font-semibold mb-4 text-gray-700">
        Resumen de la Reservacion
      </h2>
      <div class="overflow-x-auto">
        <table class="min-w-full bg-white border-gray-300 shadow-md rounded-lg">
          <thead class="bg-gray-200 text-gray-700">
            <tr>
              <th class="px-4 py-2 text-left">Producto</th>
              <th class="px-4 py-2 text-left">categoria</th>
              <th class="px-4 py-2 text-right">Precio</th>
              <th class="px-4 py-2 text-right">Cantidad</th>
              <th class="px-4 py-2 text-right">Total</th>
              <th class="px-4 py-2 text-center">Acción</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in reservacion.detalleReservaciones"
              :key="item.id"
              class="border-b"
            >
              <td class="px-4 py-2 text-left">
                {{ item.producto.nombre }} - {{ item.producto.descripcion }}
              </td>
              <td class="px-4 py-2 text-left">
                {{ item.producto.categoria.nombre }}
              </td>
              <td class="px-4 py-2 text-right">${{ item.producto.precio }}</td>
              <td class="px-4 py-2 text-right">{{ item.cantidad }}</td>
              <td class="px-4 py-2 text-right">
                ${{ ((item.producto.precio ?? 0) * item.cantidad).toFixed(2) }}
              </td>
              <td class="px-4 py-2 text-center">
                <button
                  @click="deleteItem(item)"
                  class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-700"
                >
                  x
                </button>
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-100 font-semibold">
            <tr>
              <td></td>
              <td></td>
              <td></td>
              <td coldspan="6" class="px-4 py-3 text-right">Total</td>
              <td class="px-4 py-3 text-right text-bold text-blue-600">
                ${{ (totalReservacion ?? 0).toFixed(2) }}
              </td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <div class="mt-4 text-right">
        <button
          @click="confirmarReservacion"
          class="px-6 py-3 bg-green-700 hover:bg-green-900 text-white rounded font-semibold"
        >
          Confirmar Reservacion
        </button>
      </div>
    </div>
    <!--Fin div detalle orden-->
  </div>
</template>