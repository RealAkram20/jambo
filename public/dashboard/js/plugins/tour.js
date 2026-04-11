(function(window) {
  document.addEventListener('DOMContentLoaded', function (){
    const tour = new Shepherd.Tour({
      defaultStepOptions: {
        cancelIcon: {
          enabled: true
        },
        classes: 'class-1 class-2',
        scrollTo: { behavior: 'smooth', block: 'center' },
        when: {
          cancel: function () {
            // mark tour as seen so it won't show again
            try { localStorage.setItem('iq_tour_shown', 'true'); } catch (e) {}
            IQUtils.saveSessionStorage('tour', 'true');
          }
        }
      },
    });

    tour.addSteps(
        [
      {
        title : `<h4>Menu</h4>`,
        text: `<p class="mb-0">Check the content under Menu Style. Click to view all oavailable Menu Style options for you. </p>`,
          attachTo: {
            element: '#first-tour',
            on: 'right'
          },
          buttons: [
            {
              action() {
                return this.next();
              },
              text: 'Next'
            },
          ],
          id: 'first-tour'
      },
      {
        title : `<h4>Profile Setting</h4>`,
        text: `<p class="mb-0">Configure your Profile using Profile Settings. Edit, save and update your profile from here.</p>`,
          attachTo: {
            element: '#profile-setting',
            on: 'bottom'
          },
          buttons: [
            {
              action() {
                return this.back();
              },
              classes: 'shepherd-button-secondary',
              text: 'Back'
            },
            {
              action() {
                return this.next();
              },
              text: 'Next'
            }
          ],
          id: 'dropdown1'
      },
      {
        title : `<h4>Live Customizer</h4>`,
        text: `<p class="mb-0">Transform the entire look, color, style and appearance of using Live Customizer settings. Change and copy the settings from here. </p>`,
          attachTo: {
            element: '#settingbutton',
            on: 'right'
          },
          buttons: [
            {
              action() {
                return this.back();
              },
              classes: 'shepherd-button-secondary',
              text: 'Back'
            },
            {
              action() {
              try { localStorage.setItem('iq_tour_shown', 'true'); } catch (e) {}
              IQUtils.saveSessionStorage('tour', 'true');
              return this.complete();
              },
              text: 'Done'
            }
          ],
          id: 'title'
      },
    ]
    )
    // check media screen
    if (window.matchMedia('(min-width: 1198px)').matches) {
      setTimeout(() => {
        const liveCusomizer = IQUtils.getQueryString('live-customizer')
        // don't show if live customizer is open
        if (liveCusomizer != 'open') {
          // only start if not already marked as seen and not already started in this page
          const seen = (IQUtils.getSessionStorage('tour') === 'true') || (function(){ try { return localStorage.getItem('iq_tour_shown') === 'true' } catch(e){ return false } })();
          if (!seen && !window.__iq_tour_started) {
            window.__iq_tour_started = true;
            try { localStorage.setItem('iq_tour_shown', 'true'); } catch (e) {}
            IQUtils.saveSessionStorage('tour', 'true');
            tour.start();
            $('.shepherd-modal-overlay-container').addClass('shepherd-modal-is-visible')
          }
        }
      }, 400)
    }
  })
})(window)
