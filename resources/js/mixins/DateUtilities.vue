<script>
    export default {
        methods: {
            getYearOptions(includeAdvanceYears = true) {
                let yearOptions = [];
                let startYear = 2010;
                let yearAdditions = 30;
                let systemYear = new Date().getFullYear() - startYear;

                for (let i = startYear; i <= startYear + yearAdditions + systemYear; i++) {
                    if (! includeAdvanceYears && i == (parseInt(moment().format('YYYY')) + 1)) {
                        break;
                    }

                    yearOptions.push(i);
                }

                return yearOptions;
            },

            getMonthOptions() {
                let monthOptions = [
                    'January',
                    'February',
                    'March',
                    'April',
                    'May',
                    'June',
                    'July',
                    'August',
                    'September',
                    'October',
                    'November',
                    'December'
                ];

                return moment.months();
            },

            getCurrentDate(format = 'MMM. D, YYYY h:mm:ss A'){
                return moment(new Date()).format(format);
            },

            formatDate(value, format = 'MMM, D YYYY h:mm:ss A', returnEmptyIfInvalid = true){
                let formattedDate = moment(new Date(String(value))).format(format);
                if(returnEmptyIfInvalid && formattedDate == 'Invalid date') {
                    return '';
                }
                return formattedDate;
            }
        }
    }
</script>
