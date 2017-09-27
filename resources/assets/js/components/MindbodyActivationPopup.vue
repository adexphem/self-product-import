<template lang="html">
    <div class="form-control control-buttons">
        <div v-if="showActivateView">
            <h4><b>One more step to go!</b></h4>
            <div>
                <p>Click the <span class="text-bold">Activate</span> button below to finish connecting your MINDBODY Site to Weebly.</p>
                <p>When you're done, come back here and click <span class="text-italics">Finish</span>.</p>
            </div>
        </div>

        <button id="show-modal" class="btn-primary btn-primary-mindbody" v-if="showActivateView" v-on:click="showModal">Activate</button>

        <div v-if="showFinishedView">
            <h4><b>One more step to go!</b></h4>
            <p>Click <span class="text-bold">Finish</span> when you're done activating your MINDBODY Site's connections to Weebly.</p>

            <div class="form-control control-buttons">
                <button class="btn-primary btn-primary-mindbody" v-on:click="finishConnection">Finish</button>
            </div>
        </div>

        <div id="modal" v-if="isModalVisible">
            <div class="modal-mask">
                <div class="modal-wrapper">
                    <div class="modal-container">
                        <div class="sectional-row-up">Login to your MINDBODY account below</div>
                        <div class="modal-body">
                            <iframe id="frame" v-if="loaded" :src="iframe.src" width="100%"></iframe>
                        </div>
                        <div class="sectional-row-down">
                            <span>Click <span class="text-bold">NEXT</span> when you see your Dashboard</span>
                            <span><button class="btn-primary btn-primary-mindbody" v-on:click="closeModal">NEXT</button></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>

<script>
    export default {
        data() {
            return {
                isModalVisible: false,
                showActivateView: true,
                showFinishedView: false,
                activationUrl: '',
                loaded: false,
                iframe: {
                    src: '',
                },
                siteId: '',
                jwt: ''
            }
        },

        props: {
            activationlink: '',
            studioid: '',
            _jwt: ''
        },

        methods: {
            showModal() {
                this.loaded = true;
                this.activationUrl = this.activationlink;
                this.iframe.src = this.activationlink;
                this.siteId = this.studioid;
                this.jwt = this._jwt;
                this.isModalVisible = true;
                this.showActivateView = false;
                this.showFinishedView = false;
            },

            closeModal() {
                this.isModalVisible = false;
                this.showFinishedView = true;
            },

            finishConnection() {
                window.location.href = "/mindbody/finish_connection?jwt=" + this.jwt + "&studio_id=" + this.siteId;
            }
        }
    }
</script>