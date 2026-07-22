export function useDateFormatter() {
  const locale = 'es-BO';

  // Formateo de fecha sola (DD/MM/YYYY)
  const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Intl.DateTimeFormat(locale, {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    }).format(new Date(dateString));
  };

  // Formateo de fecha y hora (DD/MM/YYYY HH:mm)
  const formatDateTime = (dateString) => {
    if (!dateString) return '';
    return new Intl.DateTimeFormat(locale, {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12: false // Cambiar a true si prefieres am/pm
    }).format(new Date(dateString));
  };

  return {
    formatDate,
    formatDateTime
  };
}