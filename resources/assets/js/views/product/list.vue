<template>

  <article>

    <product-search></product-search>

    <div class="row lk-list-product-header hide-for-small-only">
      <div class="medium-3 columns" v-for="(name, showTitle) in show" v-if="showTitle">
        <span class="lk-list-product-title">{{ name }}</span>
      </div>
    </div>

    <a class="lk-list-product-link" v-for="product in products" v-link="{ name: 'product.show', params: { id: product.id }}">
      <div class="row">
        <div class="medium-3 columns" v-for="(name, showTitle) in show" v-if="showTitle">
          <span class="lk-list-product-title show-for-small-only">{{ name }}</span>
          <span>{{ product[name] }}</span>
        </div>
      </div>
    </a>
  </article>

</template>

<script>
  import ProductSearch from '../../components/ProductSearch.vue';

  export default {

    components: {
      ProductSearch
    },

    data() {
      return {
        products: [],
        show: {
          'slug': true,
          'sku': true,
          'name': true,
          'quantity': true,
          'unitPrice': false,
          'isInventoryRequired': false,
          'isPriceVisible': false,
          'isActive': false,
          'isVisible': false,
          'isTaxable': false,
          'isShippable': false,
          'areAttachmentsEnabled': false,
        }
      };
    },

    methods: {

      fetch() {
        this.$resource('/api/products').get().then(function (res) {
          this.products = res.data;
        });
      },

    },

    route: {
      data() {
        this.fetch();
      }
    }
  }
</script>
