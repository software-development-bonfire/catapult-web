<script>
    export default {
        methods: {
            dynamicSort(property) {
                let sortOrder = 1;
                if (property[0] === "-") {
                    sortOrder = -1;
                    property = property.substr(1);
                }
                return function (a,b) {
                    let result = (a[property] < b[property]) ? -1 : (a[property] > b[property]) ? 1 : 0;
                    return result * sortOrder;
                }
            },

            optionsHandler(array, object, remove) {
                let self = this;

                if (remove) {
                    array.forEach(function(item, index) {
                        if (object === item) {
                            array.splice(index, 1);
                        }
                    });
                } else {
                    array.push(object);
                }
            },

            openUrlWithPostData(url, name, params) {
                let form = document.createElement("form");
                form.setAttribute("method", "POST");
                form.setAttribute("action", url);
                form.setAttribute("target", name);

                for (let i in params) {
                    if (params.hasOwnProperty(i)) {
                        let input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = i;
                        input.value =  typeof params[i] === 'object' ? JSON.stringify(params[i]) : params[i];
                        form.appendChild(input);
                    }
                }

                let token = document.head.querySelector('meta[name="csrf-token"]');

                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_token';
                input.value = token.content;
                form.appendChild(input);

                document.body.appendChild(form);
                window.open("", name);
                form.submit();
                document.body.removeChild(form);

                return true;
            },

            deepClone(data) {
                return JSON.parse(JSON.stringify(data));
            },

            uuid() {
                return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                    var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
                    return v.toString(16);
                });
            },

            money(value, minimumFractionDigits = 2, maximumFractionDigits = 2) {
                return Number(value.toFixed(maximumFractionDigits, minimumFractionDigits))
                    .toLocaleString('en', {
                        minimumFractionDigits: minimumFractionDigits,
                        maximumFractionDigits: maximumFractionDigits
                    });
            },

            getResponseFileName(response) {
                let contentDisposition = response.headers['content-disposition'];
                let filename = contentDisposition.replace('attachment; filename=', '').split('"').join('');
                return filename;
            },

            toTitleCase(string) {
                return string.replace(
                    /\w\S*/g,
                    function(txt) {
                        return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
                    }
                );
            },

            async downloadHttpFile(url, params, method = 'GET', responseType = 'blob') {
                let that = this;

                await axios(url, {
                    method: method,
                    url: url,
                    params: params,
                    responseType: responseType,
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                }).then(function(response) {
                    const url = window.URL.createObjectURL(new Blob([response.data]));
                    const link = document.createElement('a');
                    link.href = url;
                    link.setAttribute('download', that.getResponseFileName(response));
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                }).catch(function (error) {
                    return console.error(error);
                })
            },

            async getMinUntakenValue(
                getter,
                table,
                column,
                min,
                max,
                conditions = [],
                endpoint = '/getMinUntakenValue'
            ) {
                let that = this;

                this.$root.processing(true);

                await axios.get(endpoint, {
                    params: {
                        table: table,
                        column: column,
                        min: min,
                        max: max,
                        conditions: conditions
                    }
                }).then(function(response) {
                    that.$set(getter, column, response.data);
                });

                this.$root.processing(false);
            },

            parseFloatAndRound(value, places = 2) {
                let floatValue = parseFloat(value);
                return parseFloat(floatValue || 0).toFixed(places);
            },

            countDecimalPlaces(value) {
                let isFloat = ! isNaN(parseFloat(value));
                let decimals = value.split(".")[1];
                return isFloat && (decimals !== undefined) ? decimals.length : 0;
            },

            async base64ToImageFile(url, extension = 'jpeg') {
                return await fetch(url)
                    .then(res => res.blob())
                    .then(blob => {
                        const file = new File([blob], Date.now() + "." + extension);
                        return file;
                    });
            },

            validateIPAddress(ipaddress) {
                if (/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/.test(ipaddress)) {  
                    return true;
                }

                return false;
            }
        },
    };
</script>
