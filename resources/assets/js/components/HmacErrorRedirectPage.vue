<template>
    <div class="card_redirect_timer">You will be redirected in {{ timer }} seconds.</div>
</template>

<script>
    export default {
        data () {
            return {
                timer: '',
                url: 'https://www.weebly.com/home/'
            }
        },

        created: function () {
            this.timerTicker(5);
        },

        methods: {
            timerTicker (counter) {
                var _this = this;

                window.setInterval(function() {
                    (counter < 1) ? window.location = _this.url : _this.timer = counter--;
                }, 1000);
            }
        }
    }
</script>