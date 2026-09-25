@php
    $cameraStatePath = $getStatePath();
    $statePrefix = str_contains($cameraStatePath, '.')
        ? \Illuminate\Support\Str::beforeLast($cameraStatePath, '.').'.'
        : '';
    $photoStatePath = $statePrefix.'photo_path';
@endphp

<div
    x-data="{
        open: false,
        stream: null,
        error: '',
        uploading: false,
        preview: null,
        async startCamera() {
            this.error = ''

            if (! navigator.mediaDevices?.getUserMedia) {
                this.error = 'Este navegador no permite utilizar la cámara.'
                return
            }

            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user' },
                    audio: false,
                })
                this.open = true
                await this.$nextTick()
                this.$refs.video.srcObject = this.stream
                await this.$refs.video.play()
            } catch (error) {
                this.error = 'No se pudo abrir la cámara. Revisá el permiso del navegador.'
            }
        },
        stopCamera() {
            this.stream?.getTracks().forEach((track) => track.stop())
            this.stream = null
            this.open = false
        },
        takePhoto() {
            const video = this.$refs.video
            const canvas = this.$refs.canvas

            if (! video.videoWidth || ! video.videoHeight) {
                this.error = 'Esperá a que la cámara termine de cargar.'
                return
            }

            canvas.width = video.videoWidth
            canvas.height = video.videoHeight
            canvas.getContext('2d').drawImage(video, 0, 0)

            canvas.toBlob((blob) => {
                if (! blob) {
                    this.error = 'No se pudo obtener la fotografía.'
                    return
                }

                this.preview = URL.createObjectURL(blob)
                this.uploading = true
                this.error = ''

                const file = new File(
                    [blob],
                    `jugador-${Date.now()}.jpg`,
                    { type: 'image/jpeg' },
                )
                const fileKey = crypto.randomUUID?.()
                    ?? `${Date.now()}-${Math.random().toString(16).slice(2)}`

                $wire.upload(
                    @js($photoStatePath) + '.' + fileKey,
                    file,
                    () => {
                        this.uploading = false
                        this.stopCamera()
                    },
                    () => {
                        this.uploading = false
                        this.error = 'No se pudo cargar la foto. Intentá nuevamente.'
                    },
                )
            }, 'image/jpeg', 0.9)
        },
    }"
    x-on:camera-capture-close.window="stopCamera()"
    class="space-y-3"
>
    <button
        type="button"
        x-on:click="startCamera()"
        class="fi-btn fi-btn-size-md fi-color-primary fi-ac-btn-action"
    >
        Tomar foto con cámara
    </button>

    <p class="text-sm text-gray-600 dark:text-gray-300">
        Funciona con la webcam de la computadora y con la cámara frontal del teléfono.
    </p>

    <p x-show="error" x-text="error" x-cloak class="text-sm font-semibold text-danger-600"></p>

    <div
        x-show="open"
        x-cloak
        class="space-y-3 rounded-xl border border-gray-300 bg-gray-950 p-3"
    >
        <video x-ref="video" playsinline muted class="max-h-96 w-full rounded-lg bg-black"></video>
        <canvas x-ref="canvas" class="hidden"></canvas>

        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                x-on:click="takePhoto()"
                x-bind:disabled="uploading"
                class="fi-btn fi-btn-size-md fi-color-primary"
            >
                <span x-show="! uploading">Capturar y usar esta foto</span>
                <span x-show="uploading">Cargando foto…</span>
            </button>
            <button
                type="button"
                x-on:click="stopCamera()"
                class="fi-btn fi-btn-size-md fi-color-gray"
            >
                Cancelar
            </button>
        </div>
    </div>

    <div x-show="preview" x-cloak>
        <p class="mb-2 text-sm font-semibold text-success-700">Foto capturada correctamente</p>
        <img x-bind:src="preview" alt="Vista previa de la foto" class="max-h-56 rounded-lg border border-gray-300">
    </div>
</div>
