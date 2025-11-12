SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
 /*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
 /*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
 /*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Estructura de tabla: QRusuarios
-- --------------------------------------------------------
CREATE TABLE `QRusuarios` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `rol` VARCHAR(20) DEFAULT 'capturista',
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Estructura de tabla: QRdocumentos
-- --------------------------------------------------------
CREATE TABLE `QRdocumentos` (
  `id` CHAR(36) NOT NULL,
  `fecha_captura` DATE NOT NULL,
  `fecha_expedicion_pago` DATE DEFAULT NULL,
  `id_capturista` INT(11) NOT NULL,
  `folio_no_adeudo` VARCHAR(50) DEFAULT NULL,
  `folio_aportacion` VARCHAR(50) DEFAULT NULL,
  `subdirector` VARCHAR(100) DEFAULT '1',
  `cargo` VARCHAR(100) DEFAULT 'Subdirector de Recaudación',
  `anio_fiscal` INT(11) DEFAULT 2025,
  `tipo_predio` VARCHAR(50) DEFAULT NULL,
  `colonia` VARCHAR(100) DEFAULT NULL,
  `direccion` VARCHAR(255) DEFAULT NULL,
  `contribuyente` VARCHAR(100) DEFAULT NULL,
  `clave_catastral` VARCHAR(50) DEFAULT NULL,
  `base_gravable` DECIMAL(12,2) DEFAULT NULL,
  `bimestre` INT(11) DEFAULT NULL,
  `linea_captura` VARCHAR(100) DEFAULT NULL,
  `superficie_terreno` DECIMAL(10,2) DEFAULT NULL,
  `superficie_construccion` DECIMAL(10,2) DEFAULT NULL,
  `recibo_oficial` VARCHAR(50) DEFAULT NULL,
  `recibo_mejoras` VARCHAR(50) DEFAULT NULL,
  `costo_certificacion` DECIMAL(10,2) DEFAULT NULL,
  `tipo_documento` ENUM('no_adeudo','aportacion_mejoras') DEFAULT NULL,
  `ruta_pdf` VARCHAR(255) DEFAULT NULL,
  `url_validacion` VARCHAR(255) DEFAULT NULL,
  `estado_pdf` ENUM('activo','cancelado') DEFAULT 'activo',
  PRIMARY KEY (`id`),
  KEY `id_capturista` (`id_capturista`),
  CONSTRAINT `QRdocumentos_ibfk_1` FOREIGN KEY (`id_capturista`) REFERENCES `QRusuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
 /*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
 /*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
