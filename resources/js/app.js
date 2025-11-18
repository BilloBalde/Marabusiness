import './bootstrap';
import 'preline';

document.addEventListener('livewire:navigated', () => {
    window.HSStaticMethods.autoInit();
});

import Swal from 'sweetalert2'

window.Swal = Swal
