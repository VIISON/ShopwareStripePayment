{extends file='parent:backend/index/header.tpl'}

{block name="backend/base/header/css"}
    {$smarty.block.parent}
    
    <link type="text/css" media="all" rel="stylesheet" href="{link file='backend/stripe_payment/_resources/css/stripe-button.css'}" />
{/block}
