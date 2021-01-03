const app = new Vue({
    data() {
        return {
            listURL: ['tlgrm.eu', 'store.line.me'], //list url valid in site
            form: {
                url: "https://store.line.me/stickershop/product/13493978/en",
                is_continue: 0,
                size: 1
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

            optionSizes: [
                {id: 0, name: '50 x 50'},
                {id: 1, name: '100 x 100', select: true},
                {id: 2, name: '150 x 150'},
                {id: 3, name: '200 x 200'},
                {id: 4, name: '250 x 250'},
            ],

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
                            if (status_code == 101) {
                                showContinue.isShow = true;
                                resultResponse.isShow = true;
                                resultResponse.url = null;
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
            resultResponse.url = null;
            this.setToast({type: null, message: null});
        },


        setToast({type, message}) {
            const {toast} = this;
            toast.type = type;
            toast.message = message;

        }

    },

    computed: {

        //check valid contains from url list site allowed to continue.
        validURL() {
            const {form} = this;
            const newURL = new URL(form.url);
            return this.listURL.includes(newURL.hostname);
        }
    }

}).$mount('#app')