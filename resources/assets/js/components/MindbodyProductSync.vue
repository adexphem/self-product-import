<template lang="html">
    <div>
        <div class="img-product-sync-loader" v-if="loading">
            <img src="/images/ajax_loader_gray.gif" title="product-import-loader">
            <div class="hint">Importing products...</div>
        </div>

        <div class="card-content_inner" v-else="loading">
            <p class="card-gap_low">
                <span class="text-bold">Last Import: </span>
                <span class="last-sync">
                    <span v-if="syncData">{{ syncData.lastSyncedDate }}</span>
                    <span v-else="syncData">{{ defaultData.lastSyncedDate }}</span>
                </span>
            </p>
            <p class="card-gap_low">
                <span class="products-synced text-bold">Products Imported: </span>
                <span class="products-synced">
                    <span v-if="syncData">{{ syncData.productsSyncedCount }}</span>
                    <span v-else="syncData">{{ defaultData.productsSyncedCount }}</span>
                </span>
            </p>
        </div>

        <div class="form-control control-buttons">
            <button class="btn-primary btn-primary-mindbody" v-on:click="startSync">Import Products Now</button>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                loading: false,
                syncUrl: '',
                syncData: null,
                errors: null,
                defaultData: []
            }
        },

        props: [
            '_synclink',
            '_synceddate',
            '_syncedcount'
        ],

        created(){
            this.getDefaultData();
        },

        methods: {
            getDefaultData() {
                this.defaultData.productsSyncedCount = this._syncedcount;
                this.defaultData.lastSyncedDate = this._synceddate;
            },

            startSync() {
                let url = this._synclink;
                this.loading = true;
                var _this = this;
                axios.get(url)
                    .then(function (response) {
                        _this.loading = false;
                        _this.syncData = response.data;
                    })
                    .catch(function (error) {
                        _this.loading = false;
                        _this.errors = error.message;
                    });
            }
        }
    }
</script>