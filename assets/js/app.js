const app = new Vue({
    data() {
        return {
            form: {
                url: "https://store.line.me/stickershop/product/13493978/en",
                is_continue: 0
            },

            resultResponse: {
                isShow: false,
                url: null
            },
            showContinue: {
                isShow: false
            },

            toast: {
                type: null,
                message: null
            },

            isLoading: false,
        }
    },

    methods: {
        getLink(event, isContinue = 0) {
            const {showContinue} = this;
            const {resultResponse} = this;
            this.isLoading = true;
            this.reset();
            if (this.validURL) {
                this.form.is_continue = isContinue;
                API.post('act.php', this.form)
                    .then(({data}) => {
                        this.isLoading = false;
                        this.setToast(data);
                        //console.log(data);
                        const {message, status_code, type} = data;

                        if (type == "error") {
                            if (status_code == 100) {
                                showContinue.isShow = true;
                                resultResponse.isShow = true;
                                resultResponse.url = data.url;
                            }
                        } else if (type == "success") {
                            resultResponse.isShow = true;
                            resultResponse.url = data.url;
                            Toast.fire({
                                icon: 'success',
                                title: 'Get album sticker success!'
                            })
                        }
                    })
                    .catch((error) => {
                        console.log(error);
                    })
            } else {
                this.isLoading = false;
                Toast.fire({
                    icon: 'error',
                    title: 'We only accept url sticker from store.line.me!'
                })
            }
        },

        reset() {
            const {showContinue} = this;
            const {resultResponse} = this;
            showContinue.isShow = false;
            resultResponse.isShow = false;
            this.setToast({type: null, message: null});
        },


        setToast({type, message}) {
            const {toast} = this;
            toast.type = type;
            toast.message = message;

        }

    },

    computed: {
        validURL() {
            const {form} = this;
            let pattern = new RegExp("^https:\\/\\/store\\.line\\.me\\/");
            return !!pattern.test(form.url);
        }
    }
}).$mount('#app')