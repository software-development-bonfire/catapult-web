module.exports = {
    apps : [
        {
            name : "[CATAPULT] Queue Worker",
            script: "artisan",
            args: ["queue:work", "--tries=5"],
            exec_interpreter: "php",
            exec_mode : "fork",
            max_memory_restart : "1G",
            watch: false,
            merge_logs: true,
            autorestart: true,
        },
        {
            name : "[CATAPULT] Fetch For Sync - CDIS",
            script: "artisan",
            args: ["fetch-for-sync:cdis"],
            exec_interpreter: "php",
            exec_mode : "fork",
            max_memory_restart : "1G",
            watch: false,
            merge_logs: true,
            autorestart: true,
        },
    ]
}
