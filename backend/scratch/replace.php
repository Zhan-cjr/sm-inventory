<?php
$content = file_get_contents('app/Http/Controllers/ReportPrintController.php');

// Pesanan Pembelian
$search = "        } elseif (isset(\$filters['branch_id']['value']) && !empty(\$filters['branch_id']['value'])) {\n            \$query->where('branch_id', \$filters['branch_id']['value']);\n        }\n\n        \$orders = \$query->orderBy('po_date', 'desc')->get();";
$replace = "        } elseif (isset(\$filters['branch_id']['value']) && !empty(\$filters['branch_id']['value'])) {\n            \$query->where('branch_id', \$filters['branch_id']['value']);\n        }\n\n        if (\$searchQuery = request()->input('tableSearchQuery')) {\n            \$query->where(function(\$q) use (\$searchQuery) {\n                \$q->where('po_number', 'like', \"%{\$searchQuery}%\")\n                  ->orWhereHas('supplier', fn(\$sq) => \$sq->where('name', 'like', \"%{\$searchQuery}%\"))\n                  ->orWhereHas('branch', fn(\$bq) => \$bq->where('name', 'like', \"%{\$searchQuery}%\"));\n            });\n        }\n\n        \$orders = \$query->orderBy('po_date', 'desc')->get();";
$content = str_replace($search, $replace, $content);

// Penerimaan Barang
$search = "        } elseif (isset(\$filters['branch_id']['value']) && !empty(\$filters['branch_id']['value'])) {\n            \$query->where('branch_id', \$filters['branch_id']['value']);\n        }\n\n        \$receipts = \$query->orderBy('receipt_date', 'desc')->get();";
$replace = "        } elseif (isset(\$filters['branch_id']['value']) && !empty(\$filters['branch_id']['value'])) {\n            \$query->where('branch_id', \$filters['branch_id']['value']);\n        }\n\n        if (\$searchQuery = request()->input('tableSearchQuery')) {\n            \$query->where(function(\$q) use (\$searchQuery) {\n                \$q->where('receipt_number', 'like', \"%{\$searchQuery}%\")\n                  ->orWhereHas('supplier', fn(\$sq) => \$sq->where('name', 'like', \"%{\$searchQuery}%\"))\n                  ->orWhereHas('branch', fn(\$bq) => \$bq->where('name', 'like', \"%{\$searchQuery}%\"));\n            });\n        }\n\n        \$receipts = \$query->orderBy('receipt_date', 'desc')->get();";
$content = str_replace($search, $replace, $content);

// Retur Pembelian
$search = "        } elseif (isset(\$filters['branch_id']['value']) && !empty(\$filters['branch_id']['value'])) {\n            \$query->where('branch_id', \$filters['branch_id']['value']);\n        }\n\n        \$returns = \$query->orderBy('return_date', 'desc')->get();";
$replace = "        } elseif (isset(\$filters['branch_id']['value']) && !empty(\$filters['branch_id']['value'])) {\n            \$query->where('branch_id', \$filters['branch_id']['value']);\n        }\n\n        if (\$searchQuery = request()->input('tableSearchQuery')) {\n            \$query->where(function(\$q) use (\$searchQuery) {\n                \$q->where('return_number', 'like', \"%{\$searchQuery}%\")\n                  ->orWhereHas('supplier', fn(\$sq) => \$sq->where('name', 'like', \"%{\$searchQuery}%\"))\n                  ->orWhereHas('branch', fn(\$bq) => \$bq->where('name', 'like', \"%{\$searchQuery}%\"));\n            });\n        }\n\n        \$returns = \$query->orderBy('return_date', 'desc')->get();";
$content = str_replace($search, $replace, $content);

// Koreksi Stok
$search = "        } elseif (isset(\$filters['branch_id']['value']) && !empty(\$filters['branch_id']['value'])) {\n            \$query->where('branch_id', \$filters['branch_id']['value']);\n        }\n\n        \$adjustments = \$query->orderBy('adjustment_date', 'desc')->get();";
$replace = "        } elseif (isset(\$filters['branch_id']['value']) && !empty(\$filters['branch_id']['value'])) {\n            \$query->where('branch_id', \$filters['branch_id']['value']);\n        }\n\n        if (\$searchQuery = request()->input('tableSearchQuery')) {\n            \$query->where(function(\$q) use (\$searchQuery) {\n                \$q->where('adjustment_number', 'like', \"%{\$searchQuery}%\")\n                  ->orWhereHas('adjustmentReason', fn(\$sq) => \$sq->where('name', 'like', \"%{\$searchQuery}%\"))\n                  ->orWhereHas('branch', fn(\$bq) => \$bq->where('name', 'like', \"%{\$searchQuery}%\"));\n            });\n        }\n\n        \$adjustments = \$query->orderBy('adjustment_date', 'desc')->get();";
$content = str_replace($search, $replace, $content);

// Stock Transfer
$search = "        } elseif (isset(\$filters['from_branch_id']['value']) && !empty(\$filters['from_branch_id']['value'])) {\n            \$query->where('from_branch_id', \$filters['from_branch_id']['value']);\n        }\n\n        \$transfers = \$query->orderBy('transfer_date', 'desc')->get();";
$replace = "        } elseif (isset(\$filters['from_branch_id']['value']) && !empty(\$filters['from_branch_id']['value'])) {\n            \$query->where('from_branch_id', \$filters['from_branch_id']['value']);\n        }\n\n        if (\$searchQuery = request()->input('tableSearchQuery')) {\n            \$query->where(function(\$q) use (\$searchQuery) {\n                \$q->where('reference_number', 'like', \"%{\$searchQuery}%\")\n                  ->orWhereHas('fromBranch', fn(\$bq) => \$bq->where('name', 'like', \"%{\$searchQuery}%\"))\n                  ->orWhereHas('toBranch', fn(\$bq) => \$bq->where('name', 'like', \"%{\$searchQuery}%\"));\n            });\n        }\n\n        \$transfers = \$query->orderBy('transfer_date', 'desc')->get();";
$content = str_replace($search, $replace, $content);

file_put_contents('app/Http/Controllers/ReportPrintController.php', $content);
echo "Replacements done.";
