Intro GIF:
- Coloca aquí intro.gif para la pantalla de bienvenida animada.
- Si no existe, la app usa un placeholder (cuadro con texto "yes-ok").
- En IntroScreen.js reemplaza el <View style={styles.placeholder}> por:
  <Image source={require('../assets/intro.gif')} style={styles.gif} />
  y añade: gif: { width: 200, height: 200 }.
