<script setup>
	import { ref } from 'vue';
	const contatore = ref(0);

	function incrementa() {
		contatore.value++;
	}
</script>
<template>
  <div class="about">
    <h1>{{ contatore }}</h1>
	<h1>{{ contatore }}</h1>
	<h1>{{ contatore }}</h1>
	<h1>{{ contatore }}</h1>
	<h1><button @click="incrementa()">Incrementa</button></h1>
  </div>
</template>

<style scoped>
/* Component-specific styles only */
.about {
  min-height: 100vh;
}
</style>
