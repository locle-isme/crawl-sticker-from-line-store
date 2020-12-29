const app = new Vue({
    data() {
        return {
            form: {
                url: "https://store.line.me/stickershop/product/13493978/en",
                isContinue: false
            }
        }
    },

    methods: {
        getLink() {
            if (this.validURL) {
                API.post('act.php', this.form)
                    .then(({data}) => {

                    })
                    .catch((error) => {
                        console.log(error);
                    })
            } else {
                Toast.fire({
                    icon: 'error',
                    title: 'URL not valid , we only accept url sticker from store.line.me!'
                })
            }
        },


    },

    computed: {
        validURL() {
            const {form} = this;
            let pattern = new RegExp("^https:\\/\\/store\\.line\\.me\\/");
            return !!pattern.test(form.url);
        }
    }
}).$mount('#app')