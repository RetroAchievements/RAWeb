<?php
$large ??= true;
$navigation ??= false;
$breakpoint ??= 'lg';
$avatar ??= null;
$background ??= null;
$titleActivity ??= null;
?>
<x-section :class="($class ?? false) ? $class : 'pt-5 px-5 lg:px-0'">
    <x-section-background
        :image="$background"
        :bottom="$large ? 57 : 0"
        :scanlines="true"
        :z-index="-1"
    />
    <div>
        <div style="{{$large ? 'display:flex; flex-direction:column; justify-content: flex-end ' : ''}}">
            <x-container>
                {{-- align items end to make nav align with background --}}
                <div class="block {{ $breakpoint }}:flex {{ $large ? 'items-end' : 'items-center' }}">
                    @if($avatar)
                        <div class="mr-3">
                            {{-- only take away bottom margin at breakpoint if there's a navigation --}}
                            <div class="mb-3 {{ $navigation ? "$breakpoint:mb-0" : '' }}">
                                {{ $avatar }}
                            </div>
                        </div>
                    @endif
                    <div class="flex-1 justify-center">
                        <x-section-header>
                            <x-slot name="title">
                                {{ $title ?? null }}
                                @if($large)
                                    {{ $subTitle ?? null }}
                                    {{-- keep this class off the navbar to prevent dangling margin --}}
                                    <div class="mb-2 row vw-100 lg:w-auto" style="height:40px;padding-left:15px;padding-right:15px">
                                        @if($navigation)
                                            {{ $navigation }}
                                        @endif
                                    </div>
                                @endif
                            </x-slot>
                            <x-slot name="actions">
                                @if($large)
                                    <div class="hidden lg:block">
                                        @if($titleActivity ?? null)
                                            {{ $titleActivity }}
                                        @endif
                                    </div>
                                @else
                                    @if($actions ?? null)
                                        <div class="ml-auto">
                                            {{ $actions }}
                                        </div>
                                    @endif
                                @endif
                            </x-slot>
                        </x-section-header>
                        @if($large)
                            <div class="flex justify-between items-center overflow-x-scroll" style="height:49px">
                                {{ $stats ?? null }}
                                @if($actions ?? null)
                                    <div class="ml-auto flex gap-1 justify-end">
                                        {{ $actions }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
                @if(!$large)
                    @if($navigation)
                        {{ $navigation }}
                    @endif
                @endif
            </x-container>
        </div>
    </div>
</x-section>
