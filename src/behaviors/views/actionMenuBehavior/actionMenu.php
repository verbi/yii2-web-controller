<?php

echo $actionButtons ? Nav::widget([
                    'items' => $actionButtons,
                ]) : '';