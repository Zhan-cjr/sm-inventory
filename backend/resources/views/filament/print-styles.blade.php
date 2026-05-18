<style>
    @media print {
        /* Force body and layout to allow full height printing */
        html, body, .fi-body, .fi-layout, .fi-main, main {
            height: auto !important;
            min-height: auto !important;
            overflow: visible !important;
            display: block !important;
            background-color: white !important;
        }
        
        /* Hide UI components not needed in print */
        .fi-sidebar, 
        .fi-topbar,
        .fi-ta-header-toolbar,
        .fi-ta-pagination,
        header {
            display: none !important;
        }
        
        /* Ensure the table container doesn't clip */
        .fi-ta-content, .fi-ta-ctn {
            overflow: visible !important;
            display: block !important;
            box-shadow: none !important;
            border: none !important;
        }
        
        /* Dark mode text correction */
        .dark .fi-body {
            color: black !important;
        }
        .dark td, .dark th, .dark span {
            color: black !important;
        }
    }
</style>
