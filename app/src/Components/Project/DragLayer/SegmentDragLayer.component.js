import * as React from 'react'
import SegmentComponent from '../Row/Segment/Segment.component'

export default class SegmentDragLayer extends React.PureComponent {

    constructor(props) {
        super(props);
        this.state = {
            animate: false
        }
    }

    componentWillUnmount() {
    }

    getStyles = () =>{
        const transform = this.state.animate ? {
            transform: 'rotate(-7deg)',
            WebkitTransform: 'rotate(-7deg)',
        }: {};
        return {
            display: 'inline-block',
            transition: 'transform .5s',
            cursor: 'grabbing',
            width: '100%',
            ...transform
        }
    };
    animate = () => {
        this.setState({
            animate: true
        })
    };

    render() {
        const {type,segment} = this.props.item;

        return (
            <div style={this.getStyles()}>
                <SegmentComponent type={type}
                                  segment={segment}/>
            </div>
        )
    }

    componentDidMount() {
        setTimeout(this.animate,10);
    }
}
