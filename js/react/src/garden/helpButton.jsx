const CLIENT_ROOT = "..";

const helpButtonStyle = {
  float: "right",
  padding: 0,
  marginLeft: "auto",
  borderRadius: "50%",
  background: "#5FB021",
};

class HelpButton extends React.Component {
  constructor(props) {
    super(props);
    this.getHelpButtonId = this.getHelpButtonId.bind(this);
  }

  getHelpButtonId() {
    return this.props.title.toLowerCase().replace(/[^a-z]/g, '') + "-help";
  }

  componentDidMount() {
    const helpButtonId = this.getHelpButtonId();
    $(`#${helpButtonId}`).popover({
      title: this.props.title,
      html: true,
      trigger: "focus",
      placement: "bottom",
      content: this.props.html,
    });
  }

  render() {
    return (
      <button id={ this.getHelpButtonId() } style={ helpButtonStyle }>
        <img
          style={{ width: "1.25em" }}
          alt="help"
          src={ `${CLIENT_ROOT}/images/garden/help.png` }
        />
      </button>
    );
  }
}

export default HelpButton;